<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\Logger;
use App\Helpers\Push;
use PDO;
use Throwable;

/**
 * Диспетчер отложенных сообщений: переносит готовые партии в очередь Redis.
 */
final class ScheduledDispatchCommand extends Command
{
    public string $signature = 'scheduled:dispatch';
    public string $description = 'Move due scheduled messages to the queue; supports legacy and targeted batches.';

    /**
     * Обрабатывает готовые к отправке партии и кладёт сообщения в очередь.
     *
     * Поддерживаются «наследованные» и таргетированные рассылки, предусмотрен
     * механизм разблокировки зависших партий со статусом processing.
     *
     * @param array<int,string> $arguments Параметры (например, --limit=100)
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $limit = 100;
        $staleTtl = (int)($_ENV['SCHEDULED_PROCESSING_TTL'] ?? 600); // seconds to unlock stale "processing"
        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--limit=')) {
                $v = (int)substr($arg, 8);
                if ($v > 0) {
                    $limit = $v;
                }
            }
        }

        $db = Database::getInstance();

        // Helper to check column existence (for backward compatibility)
        $columnExists = static function (PDO $pdo, string $table, string $column): bool {
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
                $stmt->execute(['col' => $column]);
                return (bool)$stmt->fetchColumn();
            } catch (Throwable) {
                return false;
            }
        };

        try {
            // 1) Unlock stale processing batches so they can be retried
            if ($staleTtl > 0) {
                try {
                    $hasCounters = $columnExists($db, 'telegram_scheduled_messages', 'selected_count')
                        && $columnExists($db, 'telegram_scheduled_messages', 'success_count')
                        && $columnExists($db, 'telegram_scheduled_messages', 'failed_count');

                    $cutoff = date('Y-m-d H:i:s', time() - $staleTtl);
                    if ($hasCounters) {
                        // Unlock only if the batch has not fully completed yet
                        $sql = 'UPDATE telegram_scheduled_messages SET status = "pending" '
                            . 'WHERE status = "processing" '
                            . 'AND started_at IS NOT NULL '
                            . 'AND started_at < :cutoff '
                            . 'AND (success_count + failed_count) < selected_count';
                        $db->prepare($sql)->execute(['cutoff' => $cutoff]);
                    } else {
                        // Legacy schema: conservative unlock
                        $db->prepare('UPDATE telegram_scheduled_messages SET status = "pending" WHERE status = "processing" AND started_at IS NOT NULL AND started_at < :cutoff')
                            ->execute(['cutoff' => $cutoff]);
                    }
                } catch (\Throwable $e) {
                    Logger::error('Failed to unlock stale scheduled batches', ['exception' => $e]);
                }
            }

            // Fetch due, pending scheduled records up to the limit
            $stmt = $db->prepare(
                "SELECT id FROM telegram_scheduled_messages WHERE send_after <= NOW() AND status = 'pending' ORDER BY priority DESC, id ASC LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

            if ($ids === []) {
                echo 'No due scheduled messages.' . PHP_EOL;
                return 0;
            }

            $hasTargeting = $columnExists($db, 'telegram_scheduled_messages', 'target_type')
                && $columnExists($db, 'telegram_scheduled_messages', 'target_group_id');
            $hasSelectedCount = $columnExists($db, 'telegram_scheduled_messages', 'selected_count');

            $dispatchedBatches = 0;
            $dispatchedMessages = 0;

            foreach ($ids as $id) {
                // Lock row for processing (idempotent)
                $lock = $db->prepare("UPDATE telegram_scheduled_messages SET status = 'processing', started_at = IFNULL(started_at, NOW()) WHERE id = :id AND status = 'pending'");
                $lock->execute(['id' => $id]);
                if ($lock->rowCount() === 0) {
                    continue;
                }

                try {
                    if ($hasTargeting) {
                        $select = $db->prepare('SELECT user_id, method, `type`, data, priority, target_type, target_group_id FROM telegram_scheduled_messages WHERE id = :id');
                    } else {
                        $select = $db->prepare('SELECT user_id, method, `type`, data, priority FROM telegram_scheduled_messages WHERE id = :id');
                    }
                    $select->execute(['id' => $id]);
                    $row = $select->fetch();
                    if (!$row) {
                        $db->prepare("UPDATE telegram_scheduled_messages SET status = 'pending' WHERE id = :id")
                            ->execute(['id' => $id]);
                        continue;
                    }

                    try {
                        $payload = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR);
                    } catch (Throwable $e) {
                        Logger::error('Invalid scheduled message payload', ['id' => $id, 'exception' => $e]);
                        $db->prepare("UPDATE telegram_scheduled_messages SET status = 'pending' WHERE id = :id")
                            ->execute(['id' => $id]);
                        continue;
                    }

                    $recipients = [];

                    if ($hasTargeting && ($row['target_type'] ?? null)) {
                        $t = (string)$row['target_type'];
                        if ($t === 'all') {
                            $q = $db->query('SELECT user_id FROM telegram_users WHERE is_user_banned = 0 AND is_bot_banned = 0');
                            $recipients = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN) ?: []);
                        } elseif ($t === 'group') {
                            $gid = (int)($row['target_group_id'] ?? 0);
                            if ($gid > 0) {
                                $sql = 'SELECT tu.user_id FROM telegram_user_groups g '
                                    . 'JOIN telegram_user_group_user ugu ON g.id = ugu.group_id '
                                    . 'JOIN telegram_users tu ON tu.id = ugu.user_id '
                                    . 'WHERE g.id = :gid';
                                $s = $db->prepare($sql);
                                $s->execute(['gid' => $gid]);
                                $recipients = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN) ?: []);
                            }
                        } elseif ($t === 'selected') {
                            try {
                                $s = $db->prepare('SELECT target_user_id FROM telegram_scheduled_targets WHERE scheduled_id = :sid');
                                $s->execute(['sid' => $id]);
                                $recipients = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN) ?: []);
                            } catch (Throwable $e) {
                                Logger::error('Failed to load scheduled targets', ['id' => $id, 'exception' => $e]);
                            }
                        }
                    } else {
                        // Legacy: single user (user_id) or broadcast to all
                        $uid = $row['user_id'] ?? null;
                        if ($uid !== null) {
                            $recipients = [(int)$uid];
                        } else {
                            $q = $db->query('SELECT user_id FROM telegram_users WHERE is_user_banned = 0 AND is_bot_banned = 0');
                            $recipients = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN) ?: []);
                        }
                    }

                    $recipients = array_values(array_unique(array_map('intval', $recipients)));

                    if ($hasSelectedCount) {
                        try {
                            $db->prepare('UPDATE telegram_scheduled_messages SET selected_count = :cnt WHERE id = :id')
                                ->execute(['cnt' => count($recipients), 'id' => $id]);
                        } catch (Throwable) {
                            // ignore counter update errors
                        }
                    }

                    if ($recipients === []) {
                        $db->prepare("UPDATE telegram_scheduled_messages SET status = 'pending' WHERE id = :id")
                            ->execute(['id' => $id]);
                        continue;
                    }

                    $method = (string)$row['method'];
                    $type = (string)($row['type'] ?? 'push');
                    $priority = (int)$row['priority'];
                    $effectiveType = sprintf('scheduled:%s:%d', $type !== '' ? $type : 'push', (int)$id);

                    $okAll = true;
                    foreach ($recipients as $uid) {
                        $pay = is_array($payload) ? $payload : [];
                        if (!isset($pay['chat_id']) || empty($pay['chat_id'])) {
                            $pay['chat_id'] = (int)$uid;
                        }
                        $ok = Push::custom($method, $pay, (int)$uid, $effectiveType, $priority, null, $id);
                        $okAll = $okAll && $ok;
                        if ($ok) {
                            $dispatchedMessages++;
                        }
                    }

                    if (!$okAll) {
                        $db->prepare("UPDATE telegram_scheduled_messages SET status = 'pending' WHERE id = :id")
                            ->execute(['id' => $id]);
                        continue;
                    }

                    $dispatchedBatches++;
                } catch (Throwable $e) {
                    Logger::error('Scheduled dispatch failed for id ' . $id, ['exception' => $e]);
                    try {
                        $db->prepare("UPDATE telegram_scheduled_messages SET status = 'pending' WHERE id = :id")
                            ->execute(['id' => $id]);
                    } catch (Throwable) {
                        // ignore
                    }
                }
            }

            echo 'Dispatched batches: ' . $dispatchedBatches . ', messages queued: ' . $dispatchedMessages . PHP_EOL;
            return 0;
        } catch (Throwable $e) {
            Logger::error('Scheduled dispatcher error: ' . $e->getMessage(), ['exception' => $e]);
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return 1;
        }
    }
}
