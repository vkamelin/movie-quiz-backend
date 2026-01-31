<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;

/**
 * Команда сброса Redis Stream для Telegram обновлений.
 *
 * ВНИМАНИЕ: Использовать только для разработки/тестирования!
 */
final class StreamResetCommand extends Command
{
    public string $signature = 'stream:reset';
    public string $description = 'Reset Redis Stream (WARNING: deletes all messages!)';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $streamName = 'telegram:updates';
        $groupName = 'main-group';

        echo "=== Redis Stream Reset ===\n\n";
        echo "[WARNING] ВНИМАНИЕ: Все сообщения будут удалены!\n";
        echo "[WARNING] Все неподтверждённые сообщения будут потеряны!\n\n";

        try {
            $redis = RedisHelper::getInstance();
        } catch (\RedisException $e) {
            echo "[ERROR] Redis connection failed: {$e->getMessage()}\n";
            return 1;
        }

        // Интерактивное подтверждение
        echo "Продолжить? (введите 'yes' для подтверждения): ";
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line !== 'yes') {
            echo "Отменено.\n";
            return 0;
        }

        try {
            $redis->xGroup('DESTROY', $streamName, $groupName);
            echo "[OK] Consumer group '{$groupName}' destroyed\n";
        } catch (\RedisException $e) {
            echo "[INFO] Group destroy: {$e->getMessage()}\n";
        }

        try {
            $redis->del($streamName);
            echo "[OK] Stream '{$streamName}' deleted\n";
        } catch (\RedisException $e) {
            echo "[INFO] Stream delete: {$e->getMessage()}\n";
        }

        try {
            $redis->xGroup('CREATE', $streamName, $groupName, '0', true);
            echo "[OK] Consumer group '{$groupName}' created\n";
        } catch (\RedisException $e) {
            echo "[ERROR] Failed to create group: {$e->getMessage()}\n";
            return 1;
        }

        echo "\n=== Stream Reset Complete ===\n";
        return 0;
    }
}