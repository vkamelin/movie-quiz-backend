<?php
/**
 * Воркер long polling для получения обновлений от Telegram.
 *
 * Назначение:
 * - Периодически запрашивает новые обновления (getUpdates);
 * - Записывает их в Redis Stream для параллельной обработки воркерами.
 */

declare(strict_types=1);

use App\Helpers\Logger;
use App\Helpers\Database;
use App\Helpers\RedisHelper;
use Dotenv\Dotenv;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use App\Telegram\UpdateHelper;
use App\Telegram\UpdateFilter;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Graceful shutdown flag
$shutdownRequested = false;

// Signal handlers for graceful shutdown
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function () use (&$shutdownRequested) {
        Logger::info('SIGTERM received, shutting down gracefully...');
        $shutdownRequested = true;
    });
    pcntl_signal(SIGINT, function () use (&$shutdownRequested) {
        Logger::info('SIGINT received, shutting down gracefully...');
        $shutdownRequested = true;
    });
    pcntl_signal(SIGHUP, function () use (&$shutdownRequested) {
        Logger::info('SIGHUP received, will reconnect...');
    });
}

/**
 * Подключение к Redis с retry.
 */
function getRedisWithRetry(int $maxRetries = 5, int $baseDelayMs = 1000): ?\Redis
{
    $attempt = 0;
    $delayMs = $baseDelayMs;

    while ($attempt < $maxRetries) {
        try {
            $attempt++;
            $redis = RedisHelper::getInstance();
            $redis->ping();
            if ($attempt > 1) {
                Logger::info('Redis reconnected successfully', ['attempt' => $attempt]);
            }
            return $redis;
        } catch (\RedisException $e) {
            if ($attempt >= $maxRetries) {
                Logger::error('Redis connection failed after max retries', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
            Logger::warning('Redis connection failed, retrying...', [
                'attempt' => $attempt,
                'max_retries' => $maxRetries,
                'delay_ms' => $delayMs,
                'error' => $e->getMessage(),
            ]);
            usleep($delayMs * 1000);
            $delayMs = min($delayMs * 2, 30000); // Max 30 seconds
        }
    }
    return null;
}

/**
 * Инициализация stream и consumer group.
 */
function ensureStreamExists(\Redis $redis): void
{
    try {
        $redis->xGroup('CREATE', 'telegram:updates', 'main-group', '0', true);
    } catch (\RedisException $e) {
        if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
            throw $e;
        }
    }
}

try {
    $redis = getRedisWithRetry();
    if ($redis === null) {
        exit(1);
    }
    ensureStreamExists($redis);
} catch (\Exception $e) {
    Logger::error('Failed to initialize: ' . $e->getMessage());
    exit(1);
}

try {
    if ($_ENV['BOT_API_SERVER'] === 'local') {
        $apiBaseUri = 'http://' . $_ENV['BOT_LOCAL_API_HOST'] . ':' . $_ENV['BOT_LOCAL_API_PORT'];
        $apiBaseDownloadUri = '/root/telegram-bot-api/' . $_ENV['BOT_TOKEN'];
        Request::setCustomBotApiUri($apiBaseUri, $apiBaseDownloadUri);
    }

    $telegram = new Telegram($_ENV['BOT_TOKEN'], $_ENV['BOT_NAME']);
    Logger::info('Long polling started');
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    Logger::error("Telegram initialization failed: {$e->getMessage()}");
    exit();
}

// Список разрешённых типов обновлений
$allowedUpdates = [
    Update::TYPE_MESSAGE,
    Update::TYPE_EDITED_MESSAGE,
    Update::TYPE_CHANNEL_POST,
    Update::TYPE_EDITED_CHANNEL_POST,
    Update::TYPE_MESSAGE_REACTION,
    Update::TYPE_MESSAGE_REACTION_COUNT,
    Update::TYPE_INLINE_QUERY,
    Update::TYPE_CHOSEN_INLINE_RESULT,
    Update::TYPE_CALLBACK_QUERY,
    Update::TYPE_SHIPPING_QUERY,
    Update::TYPE_PRE_CHECKOUT_QUERY,
    Update::TYPE_POLL,
    Update::TYPE_POLL_ANSWER,
    Update::TYPE_MY_CHAT_MEMBER,
    Update::TYPE_CHAT_MEMBER,
    Update::TYPE_CHAT_JOIN_REQUEST,
    Update::TYPE_CHAT_BOOST,
    Update::TYPE_REMOVED_CHAT_BOOST,
];

$offset = getLongPollingOffset();
$reconnectDelay = 5000; // ms

try {
    while (!$shutdownRequested) {
        // Обработка сигналов
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        try {
            $redis = getRedisWithRetry(3, 1000);
            if ($redis === null) {
                // Ждём перед повторной попыткой
                usleep($reconnectDelay * 1000);
                $reconnectDelay = min($reconnectDelay * 2, 30000);
                continue;
            }
            $reconnectDelay = 5000;

            $updates = Request::getUpdates([
                'offset' => $offset,
                'allowed_updates' => $allowedUpdates,
                'timeout' => 30,
                'limit' => 100,
            ])->getResult();

            if (!is_array($updates)) {
                $updates = [];
            }

            if (count($updates) === 0) {
                usleep(100000);
                continue;
            }

            foreach ($updates as $update) {
                if ($shutdownRequested) {
                    break;
                }

                $updateId = $update->getUpdateId();
                $updateData = $update->getRawData();
                $updateType = $update->getUpdateType();

                Logger::debug('Received update', [
                    'id' => $updateId,
                    'type' => $updateType,
                ]);

                // Проверяем фильтры
                try {
                    $redisFilter = getRedisWithRetry();
                } catch (\Exception $e) {
                    $redisFilter = null;
                }
                $filter = new UpdateFilter(
                    $redisFilter,
                    $_ENV['TG_FILTERS_REDIS_PREFIX'] ?: 'tg:filters'
                );
                $reason = null;
                if (!$filter->shouldProcess($update, $reason)) {
                    Logger::info('Update skipped', [
                        'id' => $updateId,
                        'type' => $updateType,
                        'reason' => $reason,
                    ]);
                    continue;
                }

                // Записываем в Redis Stream
                $streamMessageId = $redis->xAdd(
                    'telegram:updates',
                    '*',
                    [
                        'update_id' => (string)$updateId,
                        'update_type' => $updateType,
                        'data' => base64_encode(json_encode($updateData)),
                        'created_at' => (string)time(),
                        'worker' => 'pending',
                    ],
                    100000,  // MAXLEN с приблизительным лимитом
                    true     // approximate maxlen
                );

                Logger::debug('Update added to stream', [
                    'id' => $updateId,
                    'stream_id' => $streamMessageId,
                ]);

                $offset = $updateId + 1;
                $redis->set(RedisHelper::REDIS_LONGPOLLING_OFFSET_KEY, $offset);
            }
        } catch (Longman\TelegramBot\Exception\TelegramException $e) {
            Logger::error("Telegram getUpdates failed. {$e->getMessage()}");
            usleep(1000000);
        } catch (\RedisException $e) {
            Logger::error("Redis error in long polling loop. {$e->getMessage()}");
            // Переподключимся в следующей итерации
        } catch (Exception $e) {
            Logger::error("Long polling error. {$e->getMessage()}");
        }
    }
} catch (Exception $e) {
    Logger::error("Fatal long polling error. {$e->getMessage()}");
}

Logger::info('Long polling worker stopped gracefully');
exit(0);

function getLongPollingOffset(): int
{
    try {
        $redis = getRedisWithRetry();
        if ($redis === null) {
            return 0;
        }
        $offset = $redis->get(RedisHelper::REDIS_LONGPOLLING_OFFSET_KEY);

        if ($offset === false) {
            $offset = 0;
        }

        $offset = (int)$offset;

        if ($offset === 0) {
            $offset = getLongPollingOffsetFromDb();
        }

        return $offset;
    } catch (\RedisException|\RuntimeException $e) {
        Logger::error("Failed to get long polling offset");
    }

    return 0;
}

function getLongPollingOffsetFromDb(): int
{
    $db = Database::getInstance();

    $stmt = $db->query("SELECT `update_id` FROM `telegram_updates` ORDER BY `created_at` DESC LIMIT 1");
    $result = $stmt->fetchColumn();
    
    if ($result === false) {
        $result = 0;
    }
    
    return (int)$result;
}
