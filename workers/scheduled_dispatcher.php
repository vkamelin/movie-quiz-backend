<?php
/**
 * Воркер планировщика отправок (scheduled dispatcher).
 *
 * Назначение:
 * - Находит отложенные задания на рассылку, срок которых наступил;
 * - Определяет получателей (все/группа/выборочно/один);
 * - Ставит персональные задачи в очередь через `App\Helpers\Push`;
 * - Помечает задание в статус `processing`, чтобы избежать повторной отправки.
 *
 * Переменные окружения:
 * - SCHEDULED_DISPATCH_LIMIT — лимит записей на один цикл обработки.
 *
 * Замечания по логированию:
 * - В лог пишутся только ошибки и важные предупреждения/инфосообщения;
 * - Пустые итерации не засоряют лог.
 */

declare(strict_types=1);

use App\Helpers\Database;
use App\Helpers\Logger;
use App\Helpers\Push;
use Dotenv\Dotenv;
use PDO;

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Very simple worker:
// - Pick due scheduled messages (status = pending)
// - Resolve recipients (all | group | selected | single)
// - Enqueue per recipient via Push helper
// - Mark message as processing to avoid re-dispatch

$limit = (int)($_ENV['SCHEDULED_DISPATCH_LIMIT'] ?? 100);
if ($limit <= 0) {
    $limit = 100;
}

Logger::info('Scheduled dispatcher worker started', ['limit' => $limit]);

while (true) {
    $started = microtime(true);

    try {
        $db = Database::getInstance();

        // 1) Fetch IDs of due pending batches up to limit
        $stmt = $db->prepare(
            "SELECT id FROM telegram_scheduled_messages WHERE send_after <= NOW() AND status = 'pending' ORDER BY priority DESC, id ASC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

        // Intentionally avoid noisy logging when there are no due messages
        // to keep logs focused on errors and important events.

        foreach ($ids as $id) {
            // 2) Lock row (idempotent)
            $lock = $db->prepare("UPDATE telegram_scheduled_messages SET status = 'processing', started_at = IFNULL(started_at, NOW()) WHERE id = :id AND status = 'pending'");
            $lock->execute(['id' => $id]);
            if ($lock->rowCount() === 0) {
                continue; // already taken by another worker
            }

            try {
                // 3) Load record with targeting if available
                $row = null;
                try {
                    $select = $db->prepare('SELECT user_id, method, `type`, data, priority, target_type, target_group_id FROM telegram_scheduled_messages WHERE id = :id');
                    $select->execute(['id' => $id]);
                    $row = $select->fetch();
                } catch (\Throwable) {
                    $select = $db->prepare('SELECT user_id, method, `type`, data, priority FROM telegram_scheduled_messages WHERE id = :id');
                    $select->execute(['id' => $id]);
                    $row = $select->fetch();
                }
                if (!$row) {
                    continue;
                }

                // 4) Parse payload
                try {
                    $payload = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable $e) {
                    Logger::error('Invalid scheduled message payload', ['id' => $id, 'exception' => $e]);
                    continue;
                }

                // 5) Resolve recipients
                $recipients = [];
                $targetType = $row['target_type'] ?? null;
                if ($targetType) {
                    $t = (string)$targetType;
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
                        } catch (\Throwable $e) {
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
                if ($recipients === []) {
                    // No recipients — immediately mark completed to avoid infinite pending/processing
                    try {
                        $db->prepare('UPDATE telegram_scheduled_messages SET selected_count = 0, status = \"completed\", completed_at = NOW() WHERE id = :id')
                            ->execute(['id' => $id]);
                    } catch (\Throwable) {
                        // ignore
                    }
                    continue;
                }

                // 6) Enqueue per recipient, ensure chat_id present
                $method = (string)$row['method'];
                $type = (string)($row['type'] ?? 'push');
                $priority = (int)$row['priority'];
                // For analytics: mark telegram_messages.type uniquely for scheduled batches, but keep user's type
                $effectiveType = sprintf('scheduled:%s:%d', $type !== '' ? $type : 'push', (int)$id);

                // Store selected_count if column exists
                try {
                    $db->prepare('UPDATE telegram_scheduled_messages SET selected_count = :cnt WHERE id = :id')
                        ->execute(['cnt' => count($recipients), 'id' => $id]);
                } catch (\Throwable) {
                    // ignore if column not present
                }

                foreach ($recipients as $uid) {
                    $pay = is_array($payload) ? $payload : [];
                    if (!isset($pay['chat_id']) || empty($pay['chat_id'])) {
                        $pay['chat_id'] = (int)$uid;
                    }
                    Push::custom($method, $pay, (int)$uid, $effectiveType, $priority, null, $id);
                }

                // 7) Mark as processing (already set) and never reset to pending here
                // This prevents re-dispatching the same batch on the next loop.
            } catch (\Throwable $e) {
                Logger::error('Scheduled dispatcher failed for id ' . $id, ['exception' => $e]);
                // Intentionally do not revert status back to pending to avoid infinite loops
            }
        }
    } catch (\Throwable $e) {
        Logger::error('Scheduled dispatcher iteration failed: ' . $e->getMessage());
    }

    // Keep a gentle cadence (at most 1 run per second)
    $elapsed = microtime(true) - $started;
    if ($elapsed < 1.0) {
        usleep((int)((1.0 - $elapsed) * 1_000_000));
    }
}
