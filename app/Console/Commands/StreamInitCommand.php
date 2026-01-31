<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;
use App\Helpers\RedisKeyHelper;

/**
 * Команда инициализации Redis Stream для Telegram обновлений.
 */
final class StreamInitCommand extends Command
{
    public string $signature = 'stream:init';
    public string $description = 'Initialize Redis Stream and Consumer Group for Telegram updates';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $streamName = 'telegram:updates';
        $groupName = 'main-group';

        echo "=== Redis Stream Initialization ===\n\n";

        try {
            $redis = RedisHelper::getInstance();
            echo "[OK] Redis connected\n";
        } catch (\RedisException $e) {
            echo "[ERROR] Redis connection failed: {$e->getMessage()}\n";
            return 1;
        }

        try {
            $redis->xGroup('CREATE', $streamName, $groupName, '0', true);
            echo "[OK] Consumer group '{$groupName}' created in stream '{$streamName}'\n";
        } catch (\RedisException $e) {
            if (str_contains($e->getMessage(), 'BUSYGROUP')) {
                echo "[INFO] Consumer group '{$groupName}' already exists\n";
            } else {
                echo "[ERROR] Failed to create group: {$e->getMessage()}\n";
                return 1;
            }
        }

        try {
            $info = $redis->xInfo('STREAM', $streamName);
            echo "\n[INFO] Stream '{$streamName}' stats:\n";
            echo "       Length: {$info['length']}\n";
            echo "       Groups: {$info['groups']}\n";
        } catch (\RedisException $e) {
            echo "[INFO] Stream info not available (may be empty)\n";
        }

        try {
            $groups = $redis->xInfo('GROUPS', $streamName);
            echo "\n[INFO] Consumer groups:\n";
            foreach ($groups as $group) {
                echo "       - {$group['name']}: {$group['consumers']} consumers, {$group['pending']} pending\n";
            }
        } catch (\RedisException $e) {
            echo "[INFO] Consumer groups info not available\n";
        }

        echo "\n=== Initialization Complete ===\n";
        return 0;
    }
}