<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace App\Helpers;

/**
 * Service for checking application health components.
 */
final class HealthService
{
    /**
     * Check health of database, Redis and worker.
     *
     * @return array{db: bool, redis: bool, worker: bool, status: string}
     */
    public static function check(): array
    {
        $db = self::checkDb();
        $redis = self::checkRedis();
        $worker = self::checkWorker();

        return [
            'db' => $db,
            'redis' => $redis,
            'worker' => $worker,
            'status' => ($db && $redis && $worker) ? 'ok' : 'fail',
        ];
    }

    /**
     * Check database connection.
     */
    private static function checkDb(): bool
    {
        try {
            Database::getInstance()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check Redis connection.
     */
    private static function checkRedis(): bool
    {
        try {
            $redis = RedisHelper::getInstance();
            if (method_exists($redis, 'isConnected')) {
                return (bool)$redis->isConnected();
            }
            return $redis->ping() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check whether required worker processes are running under Supervisor.
     */
    private static function checkWorker(): bool
    {
        try {
            $output = [];
            $returnVar = 0;
            // Allow querying Supervisor in another container via env-configured server URL
            $serverUrl = trim((string)($_ENV['SUPERVISOR_SERVER_URL'] ?? ''));
            $user = trim((string)($_ENV['SUPERVISOR_USER'] ?? ''));
            $pass = trim((string)($_ENV['SUPERVISOR_PASS'] ?? ''));

            $cmd = 'supervisorctl';
            if ($serverUrl !== '') {
                $cmd .= ' -s ' . escapeshellarg($serverUrl);
            }
            if ($user !== '') {
                $cmd .= ' -u ' . escapeshellarg($user);
            }
            if ($pass !== '') {
                $cmd .= ' -p ' . escapeshellarg($pass);
            }
            $cmd .= ' status';

            @exec($cmd, $output, $returnVar);
            if ($returnVar !== 0) {
                return false;
            }

            $groups = [
                'gpt' => '/^gpt:gpt-\d+\s+RUNNING\b/',
                'tg' => '/^tg:tg-\d+\s+RUNNING\b/',
            ];

            foreach ($groups as $group => $pattern) {
                $matched = false;
                foreach ($output as $line) {
                    if (str_starts_with($line, $group . ':' . $group . '-')) {
                        $matched = true;
                        if (! preg_match($pattern, $line)) {
                            return false;
                        }
                    }
                }
                if (! $matched) {
                    return false;
                }
            }

            $lpLines = array_values(array_filter(
                $output,
                static fn (string $line): bool => str_starts_with($line, 'lp')
            ));

            if (count($lpLines) !== 1 || ! preg_match('/^lp\s+RUNNING\b/', $lpLines[0])) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
