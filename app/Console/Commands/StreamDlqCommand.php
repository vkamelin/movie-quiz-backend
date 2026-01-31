<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\RedisHelper;

/**
 * Команда просмотра Dead Letter Queue для проблемных сообщений.
 */
final class StreamDlqCommand extends Command
{
    public string $signature = 'stream:dlq';
    public string $description = 'Show Dead Letter Queue messages';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $dlqStream = 'telegram:updates:dlq';
        $count = isset($arguments[0]) ? (int)$arguments[0] : 20;

        echo "=== Dead Letter Queue ===\n\n";

        try {
            $redis = RedisHelper::getInstance();
        } catch (\RedisException $e) {
            echo "[ERROR] Redis connection failed: {$e->getMessage()}\n";
            return 1;
        }

        try {
            $messages = $redis->xRevRange($dlqStream, '+', '-', $count);

            if (empty($messages)) {
                echo "[INFO] Dead Letter Queue is empty\n";
                return 0;
            }

            echo "Found " . count($messages) . " messages:\n\n";

            foreach ($messages as $id => $msgData) {
                $originalId = isset($msgData['original_id']) ? $msgData['original_id'] : 'N/A';
                $worker = isset($msgData['worker']) ? $msgData['worker'] : 'N/A';
                $error = isset($msgData['error']) ? $msgData['error'] : 'N/A';
                $createdAt = isset($msgData['created_at']) ? (int)$msgData['created_at'] : 0;

                echo "--- Message: {$id} ---\n";
                echo "Original ID: {$originalId}\n";
                echo "Worker: {$worker}\n";
                echo "Error: {$error}\n";
                echo "Created at: " . date('Y-m-d H:i:s', $createdAt) . "\n";

                if (isset($msgData['data'])) {
                    $updateData = json_decode($msgData['data'], true);
                    echo "Update data preview: " . json_encode($updateData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                }
                echo "\n";
            }

            // Очистка DLQ
            echo "\nClear DLQ? (yes/no): ";
            $handle = fopen('php://stdin', 'r');
            $line = trim(fgets($handle));
            fclose($handle);

            if ($line === 'yes') {
                $redis->del($dlqStream);
                echo "[OK] Dead Letter Queue cleared\n";
            }

        } catch (\RedisException $e) {
            echo "[ERROR] Failed to get DLQ: {$e->getMessage()}\n";
            return 1;
        }

        return 0;
    }
}