<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Database;
use App\Helpers\HealthService;
use App\Helpers\RedisHelper;
use App\Helpers\View;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер системной информации.
 */
final class SystemController
{
    /**
     * Отображает системные параметры и команды для воркеров.
     */
    public function index(Req $req, Res $res): Res
    {
        $health = HealthService::check();
        // Helper to detect sensitive keys
        $isSensitive = static function (string $key): bool {
            return (bool)preg_match('/(SECRET|PASS|PASSWORD|TOKEN|KEY|PRIVATE|AUTH|DSN)/i', $key);
        };

        // Load only variables explicitly defined in .env file
        // .env lives at project root; from app/Controllers/Dashboard go up 3 levels
        $envFile = dirname(__DIR__, 3) . '/.env';
        $envFromFile = [];
        if (is_readable($envFile)) {
            $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $trim = ltrim($line);
                if ($trim === '' || str_starts_with($trim, '#')) {
                    continue;
                }
                if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/', $trim, $m)) {
                    continue;
                }
                $key = $m[1];
                $raw = $m[2];
                // strip inline comments only if unquoted
                $val = $raw;
                $val = trim($val);
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                } else {
                    // cut off trailing comments
                    $hashPos = strpos($val, '#');
                    if ($hashPos !== false) {
                        $val = rtrim(substr($val, 0, $hashPos));
                    }
                }
                $envFromFile[$key] = $val;
            }
        }
        ksort($envFromFile);

        // Mask sensitive values from .env
        $masked = [];
        foreach ($envFromFile as $k => $val) {
            if ($isSensitive($k)) {
                $masked[$k] = '*** hidden ***';
            } else {
                $masked[$k] = $val;
            }
        }

        // Load full config array
        /** @var array $configArr */
        $configArr = require __DIR__ . '/../../Config/config.php';

        // Mask sensitive values in config as well (recursively)
        $maskConfig = static function (array $arr) use (&$maskConfig, $isSensitive): array {
            $out = [];
            foreach ($arr as $k => $v) {
                $key = is_int($k) ? (string)$k : (string)$k;
                if (is_array($v)) {
                    $out[$k] = $maskConfig($v);
                } else {
                    $out[$k] = $isSensitive($key) ? '*** hidden ***' : $v;
                }
            }
            return $out;
        };
        $configMasked = $maskConfig($configArr);

        $workerCommands = [
            'status' => [
                'supervisorctl status gpt:* tg:* lp',
                'tail -n 100 storage/logs/*.log',
            ],
            'restart' => [
                'supervisorctl restart gpt:* tg:* lp',
            ],
        ];

        $queueSizes = null;
        $sendSpeed = null;
        if (filter_var($_ENV['TELEMETRY_ENABLED'] ?? false, FILTER_VALIDATE_BOOL)) {
            try {
                $redis = RedisHelper::getInstance();
                $queueSizes = [
                    'p2' => (int)$redis->lLen('telegram:queue:2'),
                    'p1' => (int)$redis->lLen('telegram:queue:1'),
                    'p0' => (int)$redis->lLen('telegram:queue:0'),
                    'dlq' => (int)$redis->lLen('telegram:dlq'),
                ];
            } catch (\RedisException) {
                $queueSizes = null;
            }

            try {
                $db = Database::getInstance();
                $stmt = $db->query(
                    'SELECT COUNT(*) FROM telegram_messages ' .
                    "WHERE status='success' AND processed_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
                );
                $sendSpeed = (int)$stmt->fetchColumn();
            } catch (\Throwable) {
                $sendSpeed = null;
            }
        }

        $data = [
            'title' => 'System',
            'health' => $health,
            'env' => $masked,
            'config' => $configMasked,
            'workerCommands' => $workerCommands,
            'queueSizes' => $queueSizes,
            'sendSpeed' => $sendSpeed,
        ];

        return View::render($res, 'dashboard/system.php', $data, 'layouts/main.php');
    }
}
