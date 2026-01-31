<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

/**
 * Helper для создания отложенных рассылок по целям: все, группа, выбранные.
 */
final class Scheduled
{
    /**
     * Checks if a column exists in the given table.
     */
    private static function columnExists(\PDO $db, string $table, string $column): bool
    {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
            $stmt->execute(['col' => $column]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Создает запись об отложенной рассылке и, при необходимости, связанные цели.
     * Ожидается, что БД имеет таблицу `telegram_scheduled_messages` с полями:
     *   id, method, type, data, priority, send_after, status, target_type, target_group_id, created_at
     * и таблицу `telegram_scheduled_targets` для хранения фиксированных получателей (selected):
     *   scheduled_id, target_user_id
     *
     * @param string $method   Метод Telegram Bot API (например, sendMessage)
     * @param array  $payload  Параметры отправки (данные для API)
     * @param string $type     Логический тип сообщения (push/message/photo и т.п.)
     * @param int    $priority Приоритет очереди: 2,1,0
     * @param string $sendAfter Дата/время отправки в будущем (Y-m-d H:i:s)
     * @param array  $target   ['type' => 'all'|'group'|'selected', 'group_id' => int?, 'user_ids' => int[]?]
     */
    public static function createBatch(
        string $method,
        array $payload,
        string $type,
        int $priority,
        string $sendAfter,
        array $target
    ): bool {
        $db = Database::getInstance();

        if (empty($target['type']) || !in_array($target['type'], ['all', 'group', 'selected'], true)) {
            Logger::error('Scheduled.createBatch: invalid target type', ['target' => $target]);
            return false;
        }
        $targetType = $target['type'];
        $targetGroupId = null;
        $fixedUserIds = [];
        if ($targetType === 'group') {
            $gid = (int)($target['group_id'] ?? 0);
            if ($gid <= 0) {
                Logger::error('Scheduled.createBatch: group_id is required for group target');
                return false;
            }
            $targetGroupId = $gid;
        } elseif ($targetType === 'selected') {
            $fixedUserIds = array_values(array_unique(array_map('intval', (array)($target['user_ids'] ?? []))));
            $fixedUserIds = array_values(array_filter($fixedUserIds, static fn ($v) => $v > 0));
            if (!$fixedUserIds) {
                Logger::error('Scheduled.createBatch: user_ids required for selected target');
                return false;
            }
        }

        try {
            $db->beginTransaction();

            // Detect if targeting columns exist (for backward compatibility before migration)
            $hasTargeting = self::columnExists($db, 'telegram_scheduled_messages', 'target_type')
                && self::columnExists($db, 'telegram_scheduled_messages', 'target_group_id');

            if ($hasTargeting) {
                $stmt = $db->prepare(
                    'INSERT INTO `telegram_scheduled_messages` (`method`, `type`, `data`, `priority`, `send_after`, `status`, `target_type`, `target_group_id`, `created_at`) '
                    . 'VALUES (:method, :type, :data, :priority, :send_after, :status, :target_type, :target_group_id, NOW())'
                );
                $stmt->execute([
                    'method' => $method,
                    'type' => $type,
                    'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'priority' => $priority,
                    'send_after' => $sendAfter,
                    'status' => 'pending',
                    'target_type' => $targetType,
                    'target_group_id' => $targetGroupId,
                ]);
            } else {
                // Legacy schema: allow only broadcast to all users; no targeting columns yet
                if ($targetType !== 'all') {
                    Logger::error('Scheduled.createBatch: targeting not available until DB migration (only type=all allowed)', [
                        'requested_type' => $targetType,
                    ]);
                    $db->rollBack();
                    return false;
                }
                $stmt = $db->prepare(
                    'INSERT INTO `telegram_scheduled_messages` (`method`, `type`, `data`, `priority`, `send_after`, `status`, `created_at`) '
                    . 'VALUES (:method, :type, :data, :priority, :send_after, :status, NOW())'
                );
                $stmt->execute([
                    'method' => $method,
                    'type' => $type,
                    'data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'priority' => $priority,
                    'send_after' => $sendAfter,
                    'status' => 'pending',
                ]);
            }

            $scheduledId = (int)$db->lastInsertId();

            if ($hasTargeting && $targetType === 'selected' && $fixedUserIds) {
                $ins = $db->prepare('INSERT INTO `telegram_scheduled_targets` (`scheduled_id`, `target_user_id`) VALUES (:sid, :uid)');
                foreach ($fixedUserIds as $uid) {
                    $ins->execute(['sid' => $scheduledId, 'uid' => $uid]);
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            try {
                $db->rollBack();
            } catch (Throwable) {
            }
            Logger::error('Failed to create scheduled batch', [
                'exception' => $e,
                'target' => $target,
            ]);
        }

        return false;
    }
}
