<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;

/**
 * Команда просмотра уведомлений о падениях процессов от supervisor.
 */
final class SupervisorNotificationsCommand extends Command
{
    public string $signature = 'supervisor:notifications';
    public string $description = 'Show supervisor crash notifications';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $key = 'supervisor:notifications';
        $count = isset($arguments[0]) ? (int)$arguments[0] : 20;

        echo "=== Supervisor Notifications ===\n\n";

        try {
            $redis = RedisHelper::getInstance();
        } catch (\RedisException $e) {
            echo "[ERROR] Redis connection failed: {$e->getMessage()}\n";
            return 1;
        }

        try {
            $notifications = $redis->lRange($key, 0, $count - 1);

            if (empty($notifications)) {
                echo "[INFO] No notifications found\n";
                return 0;
            }

            echo "Found " . count($notifications) . " notifications:\n\n";

            foreach (array_reverse($notifications) as $notification) {
                $data = json_decode($notification, true);
                if ($data === null) {
                    continue;
                }

                $expected = $data['expected'] ? ' [EXPECTED]' : ' [UNEXPECTED]';
                $type = str_contains($data['event'], 'FATAL') ? 'FATAL' : 'EXITED';

                echo "─── {$type}{$expected} ───\n";
                echo "Process: {$data['process']}\n";
                echo "PID: {$data['pid']}\n";
                echo "Exit code: {$data['exit_code']}\n";
                echo "Time: " . date('Y-m-d H:i:s', $data['timestamp']) . "\n";
                echo "Host: {$data['hostname']}\n";
                echo "\n";
            }

            // Очистка уведомлений
            echo "\nClear notifications? (yes/no): ";
            $handle = fopen('php://stdin', 'r');
            $line = trim(fgets($handle));
            fclose($handle);

            if ($line === 'yes') {
                $redis->del($key);
                echo "[OK] Notifications cleared\n";
            }

        } catch (\RedisException $e) {
            echo "[ERROR] Failed to get notifications: {$e->getMessage()}\n";
            return 1;
        }

        return 0;
    }
}