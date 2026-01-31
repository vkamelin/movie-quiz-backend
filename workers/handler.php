<?php
/**
 * Воркер-обработчик обновлений из Redis Stream.
 *
 * Назначение:
 * - Читает сообщения из Redis Stream (telegram:updates);
 * - Обрабатывает обновления параллельно с другими воркерами;
 * - Подтверждает обработку через XACK.
 *
 * Запуск: php workers/handler.php <worker-name>
 * Пример: php workers/handler.php worker-1
 */

declare(strict_types=1);

use App\Helpers\Logger;
use App\Helpers\RedisHelper;
use App\Helpers\RedisKeyHelper;
use Dotenv\Dotenv;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use App\Handlers\Telegram\CallbackQueryHandler;
use App\Handlers\Telegram\MessageHandler;
use App\Handlers\Telegram\EditedMessageHandler;
use App\Handlers\Telegram\ChannelPostHandler;
use App\Handlers\Telegram\EditedChannelPostHandler;
use App\Handlers\Telegram\InlineQueryHandler;
use App\Handlers\Telegram\MessageReactionHandler;
use App\Handlers\Telegram\MessageReactionCountHandler;
use App\Handlers\Telegram\ChosenInlineResultHandler;
use App\Handlers\Telegram\ShippingQueryHandler;
use App\Handlers\Telegram\PreCheckoutQueryHandler;
use App\Handlers\Telegram\PollHandler;
use App\Handlers\Telegram\PollAnswerHandler;
use App\Handlers\Telegram\MyChatMemberHandler;
use App\Handlers\Telegram\ChatMemberHandler;
use App\Handlers\Telegram\ChatJoinRequestHandler;
use App\Handlers\Telegram\ChatBoostHandler;
use App\Handlers\Telegram\RemovedChatBoostHandler;
use App\Telegram\UpdateHelper as TelegramUpdateHelper;
use App\Telegram\UpdateHelper;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Graceful shutdown flag
$shutdownRequested = false;
$pendingMessageId = null;

// Signal handlers for graceful shutdown
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function () use (&$shutdownRequested, &$pendingMessageId) {
        Logger::info('SIGTERM received, shutting down gracefully...', [
            'pending_message' => $pendingMessageId,
        ]);
        $shutdownRequested = true;
    });
    pcntl_signal(SIGINT, function () use (&$shutdownRequested, &$pendingMessageId) {
        Logger::info('SIGINT received, shutting down gracefully...', [
            'pending_message' => $pendingMessageId,
        ]);
        $shutdownRequested = true;
    });
    pcntl_signal(SIGHUP, function () {
        Logger::info('SIGHUP received, will reconnect...');
    });
}

// Имя воркера передаётся как аргумент
$workerName = $argv[1] ?? 'worker-' . getmypid();

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
            $delayMs = min($delayMs * 2, 30000);
        }
    }
    return null;
}

/**
 * Добавление сообщения в Dead Letter Queue.
 */
function addToDeadLetterQueue(\Redis $redis, string $messageId, array $data, string $error): void
{
    try {
        $redis->xAdd('telegram:updates:dlq', '*', [
            'original_id' => $messageId,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'error' => $error,
            'worker' => $this->workerName ?? 'unknown',
            'created_at' => (string)time(),
        ], ['MAXLEN', '~', 10000, 'LIMIT', 100]);
    } catch (\Exception $e) {
        Logger::error('Failed to add message to DLQ', [
            'message_id' => $messageId,
            'error' => $e->getMessage(),
        ]);
    }
}

try {
    $redis = getRedisWithRetry();
    if ($redis === null) {
        exit(1);
    }
} catch (\Exception $e) {
    Logger::error('Redis connection failed: ' . $e->getMessage());
    exit(1);
}

try {
    if ($_ENV['BOT_API_SERVER'] === 'local') {
        $apiBaseUri = 'http://' . $_ENV['BOT_LOCAL_API_HOST'] . ':' . $_ENV['BOT_LOCAL_API_PORT'];
        $apiBaseDownloadUri = '/root/telegram-bot-api/' . $_ENV['BOT_TOKEN'];
        Request::setCustomBotApiUri($apiBaseUri, $apiBaseDownloadUri);
    }

    $telegram = new Telegram($_ENV['BOT_TOKEN'], $_ENV['BOT_NAME']);
    Logger::info("Handler worker started: {$workerName}");
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    Logger::error("Telegram initialization failed: {$e->getMessage()}");
    exit();
}

while (!$shutdownRequested) {
    // Обработка сигналов
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }

    try {
        // Читаем сообщения из stream с блокировкой
        $messages = $redis->xReadGroup(
            'main-group',
            $workerName,
            ['telegram:updates' => '>'],
            1,
            0
        );

        if ($messages === false || empty($messages)) {
            continue;
        }

        foreach ($messages as $streamName => $streamMessages) {
            foreach ($streamMessages as $messageId => $data) {
                if ($shutdownRequested) {
                    // Если есть обрабатываемое сообщение — сохраняем в DLQ
                    if ($pendingMessageId !== null) {
                        Logger::info('Saving pending message to DLQ before shutdown', [
                            'message_id' => $pendingMessageId,
                        ]);
                    }
                    break 2;
                }

                $pendingMessageId = $messageId;

                try {
                    Logger::debug('Processing message', [
                        'worker' => $workerName,
                        'message_id' => $messageId,
                        'update_id' => $data['update_id'] ?? 'unknown',
                    ]);

                    // Декодируем данные обновления
                    $updateRaw = json_decode(base64_decode($data['data']), true);

                    if ($updateRaw === null) {
                        Logger::error('Failed to decode update data', [
                            'message_id' => $messageId,
                        ]);
                        $redis->xAck('telegram:updates', 'main-group', $messageId);
                        continue;
                    }

                    // Дедупликация
                    $dedupKey = RedisKeyHelper::key('telegram', 'stream_update', (string)$data['update_id']);
                    $stored = $redis->set($dedupKey, 1, ['nx', 'ex' => 60]);
                    if ($stored === false) {
                        Logger::info('Duplicate update skipped from stream', [
                            'worker' => $workerName,
                            'update_id' => $data['update_id'],
                        ]);
                        $redis->xAck('telegram:updates', 'main-group', $messageId);
                        continue;
                    }

                    // Создаём объект Update
                    $update = new Update($updateRaw, $telegram->getBotUsername());
                    $updateType = $update->getUpdateType();

                    // Сохраняем в БД
                    saveUpdateToDatabase($update, $updateType);

                    // Обрабатываем обновление
                    $result = handleUpdate($update, $updateType);

                    if ($result === true) {
                        Logger::debug('Update processed successfully', [
                            'worker' => $workerName,
                            'update_id' => $update->getUpdateId(),
                        ]);
                    } else {
                        Logger::warning('Update handler returned false', [
                            'worker' => $workerName,
                            'update_id' => $update->getUpdateId(),
                            'result' => $result,
                        ]);
                    }

                    // Подтверждаем обработку
                    $redis->xAck('telegram:updates', 'main-group', $messageId);
                    $pendingMessageId = null;

                } catch (Exception $e) {
                    Logger::error('Error processing message', [
                        'worker' => $workerName,
                        'message_id' => $messageId,
                        'error' => $e->getMessage(),
                    ]);

                    // Добавляем в Dead Letter Queue
                    addToDeadLetterQueue($redis, $messageId, $data, $e->getMessage());

                    // Подтверждаем, чтобы не блокировать очередь
                    try {
                        $redis->xAck('telegram:updates', 'main-group', $messageId);
                        $pendingMessageId = null;
                    } catch (Exception $ackException) {
                        Logger::error('Failed to ACK message', [
                            'worker' => $workerName,
                            'message_id' => $messageId,
                            'error' => $ackException->getMessage(),
                        ]);
                    }
                }
            }
        }

    } catch (Longman\TelegramBot\Exception\TelegramException $e) {
        Logger::error("Telegram error: {$e->getMessage()}");
        usleep(1000000);
    } catch (\RedisException $e) {
        Logger::error("Redis error: {$e->getMessage()}");
        // Попытка переподключения
        $redis = getRedisWithRetry();
        if ($redis === null) {
            usleep(1000000);
        }
    } catch (Exception $e) {
        Logger::error("Handler error: {$e->getMessage()}");
        usleep(100000);
    }
}

Logger::info("Handler worker stopped gracefully: {$workerName}");
exit(0);

function saveUpdateToDatabase(Update $update, string $updateType): void
{
    try {
        $db = \App\Helpers\Database::getInstance();

        $userId = UpdateHelper::getUserId($update);
        if ($userId === null) {
            $typesNoUserIdArray = [
                Update::TYPE_POLL,
                Update::TYPE_MESSAGE_REACTION_COUNT,
                Update::TYPE_CHAT_BOOST,
                Update::TYPE_REMOVED_CHAT_BOOST
            ];
            if (in_array($updateType, $typesNoUserIdArray, true)) {
                $userId = 0;
            } else {
                Logger::warning('User ID not found in update', [
                    'update_id' => $update->getUpdateId(),
                ]);
                $userId = 0;
            }
        }

        $messageReaction = TelegramUpdateHelper::getMessageReaction($update);
        $messageReactionCount = TelegramUpdateHelper::getMessageReactionCount($update);

        $messageId = match ($updateType) {
            Update::TYPE_MESSAGE => $update->getMessage()?->getMessageId(),
            Update::TYPE_EDITED_MESSAGE => $update->getEditedMessage()?->getMessageId(),
            Update::TYPE_CHANNEL_POST => $update->getChannelPost()?->getMessageId(),
            Update::TYPE_EDITED_CHANNEL_POST => $update->getEditedChannelPost()?->getMessageId(),
            Update::TYPE_MESSAGE_REACTION => $messageReaction['message_id'] ?? null,
            Update::TYPE_MESSAGE_REACTION_COUNT => $messageReactionCount['message_id'] ?? null,
            default => null,
        };

        $date = match ($updateType) {
            Update::TYPE_MESSAGE => $update->getMessage()?->getDate(),
            Update::TYPE_EDITED_MESSAGE => $update->getEditedMessage()?->getEditDate() ?? time(),
            Update::TYPE_CHANNEL_POST => $update->getChannelPost()?->getDate(),
            Update::TYPE_EDITED_CHANNEL_POST => $update->getEditedChannelPost()?->getEditDate() ?? time(),
            Update::TYPE_MESSAGE_REACTION => $messageReaction['date'] ?? time(),
            Update::TYPE_MESSAGE_REACTION_COUNT => $messageReactionCount['date'] ?? time(),
            Update::TYPE_MY_CHAT_MEMBER => $update->getMyChatMember()?->getDate(),
            Update::TYPE_CHAT_MEMBER => $update->getChatMember()?->getDate(),
            Update::TYPE_CHAT_JOIN_REQUEST => $update->getChatJoinRequest()?->getDate(),
            default => time(),
        };

        $stmt = $db->prepare(
            "INSERT INTO `telegram_updates` (`update_id`, `user_id`, `message_id`, `type`, `data`, `sent_at`) VALUES (:update_id, :user_id, :message_id, :type, :data, :sent_at)"
        );
        $stmt->execute([
            'update_id' => $update->getUpdateId(),
            'user_id' => $userId,
            'message_id' => $messageId,
            'type' => $updateType,
            'data' => json_encode($update->getRawData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'sent_at' => date('Y-m-d H:i:s', $date)
        ]);
    } catch (\Exception $e) {
        Logger::error('Failed to save update to database', [
            'update_id' => $update->getUpdateId(),
            'error' => $e->getMessage(),
        ]);
    }
}

function handleUpdate(Update $update, string $updateType): bool
{
    $handler = match ($updateType) {
        Update::TYPE_MESSAGE => new MessageHandler(),
        Update::TYPE_EDITED_MESSAGE => new EditedMessageHandler(),
        Update::TYPE_CHANNEL_POST => new ChannelPostHandler(),
        Update::TYPE_EDITED_CHANNEL_POST => new EditedChannelPostHandler(),
        Update::TYPE_INLINE_QUERY => new InlineQueryHandler(),
        Update::TYPE_CHOSEN_INLINE_RESULT => new ChosenInlineResultHandler(),
        Update::TYPE_CALLBACK_QUERY => new CallbackQueryHandler(),
        Update::TYPE_SHIPPING_QUERY => new ShippingQueryHandler(),
        Update::TYPE_PRE_CHECKOUT_QUERY => new PreCheckoutQueryHandler(),
        Update::TYPE_MESSAGE_REACTION => new MessageReactionHandler(),
        Update::TYPE_MESSAGE_REACTION_COUNT => new MessageReactionCountHandler(),
        Update::TYPE_POLL => new PollHandler(),
        Update::TYPE_POLL_ANSWER => new PollAnswerHandler(),
        Update::TYPE_MY_CHAT_MEMBER => new MyChatMemberHandler(),
        Update::TYPE_CHAT_MEMBER => new ChatMemberHandler(),
        Update::TYPE_CHAT_JOIN_REQUEST => new ChatJoinRequestHandler(),
        'chat_boost' => new ChatBoostHandler(),
        'removed_chat_boost' => new RemovedChatBoostHandler(),
        default => null,
    };

    if ($handler === null) {
        Logger::warning('No handler found for update type', [
            'type' => $updateType,
        ]);
        return false;
    }

    return $handler->handle($update);
}