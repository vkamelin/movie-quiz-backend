<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;

/**
 * Команда мониторинга Redis Stream для Telegram обновлений.
 */
final class StreamStatsCommand extends Command
{
    public string $signature = 'stream:stats';
    public string $description = 'Show Redis Stream statistics and consumer status';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $streamName = 'telegram:updates';
        $dlqStream = 'telegram:updates:dlq';

        echo "=== Telegram Updates Stream Stats ===\n\n";

        try {
            $redis = RedisHelper::getInstance();
        } catch (\RedisException $e) {
            echo "[ERROR] Redis connection failed: {$e->getMessage()}\n";
            return 1;
        }

        try {
            $info = $redis->xInfo('STREAM', $streamName);
            echo "Stream: {$streamName}\n";
            echo "  Length: {$info['length']}\n";
            echo "  Groups: {$info['groups']}\n\n";
        } catch (\RedisException $e) {
            echo "[ERROR] Failed to get stream info: {$e->getMessage()}\n";
            return 1;
        }

        try {
            $groupInfo = $redis->xInfo('GROUPS', $streamName);
            echo "Consumer Groups:\n";
            foreach ($groupInfo as $group) {
                echo "  {$group['name']}:\n";
                echo "    Consumers: {$group['consumers']}\n";
                echo "    Pending: {$group['pending']}\n";
                echo "    Min ID: {$group['min']}\n";
                echo "    Max ID: {$group['max']}\n\n";
            }
        } catch (\RedisException $e) {
            echo "[WARNING] Failed to get groups info: {$e->getMessage()}\n\n";
        }

        try {
            $consumers = $redis->xInfo('CONSUMERS', $streamName, 'main-group');
            echo "Consumers (main-group):\n";
            foreach ($consumers as $consumer) {
                $idle = isset($consumer['idle']) ? round($consumer['idle'] / 1000) . 's' : 'N/A';
                echo "  {$consumer['name']}:\n";
                echo "    Pending: {$consumer['pending']}\n";
                echo "    Idle: {$idle}\n\n";
            }
        } catch (\RedisException $e) {
            echo "[WARNING] Failed to get consumers info: {$e->getMessage()}\n\n";
        }

        // Dead Letter Queue stats
        try {
            $dlqInfo = $redis->xInfo('STREAM', $dlqStream);
            echo "Dead Letter Queue: {$dlqStream}\n";
            echo "  Length: {$dlqInfo['length']}\n\n";
        } catch (\RedisException $e) {
            echo "Dead Letter Queue: Empty or not exists\n\n";
        }

        try {
            $consumers = $redis->xInfo('CONSUMERS', $streamName, 'main-group');
            $totalPending = array_sum(array_column($consumers, 'pending'));
            echo "Total pending messages: {$totalPending}\n";
        } catch (\RedisException $e) {
            // Ignore
        }

        echo "\n=== End of Stats ===\n";
        return 0;
    }
}