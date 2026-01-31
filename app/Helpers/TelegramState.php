<?php

declare(strict_types=1);

namespace App\Helpers;

use JsonException;
use RuntimeException;

class TelegramState
{
    private static function key(int $userId): string
    {
        return RedisKeyHelper::key('telegram', 'states', $userId . ':');
    }

    /**
     * Получить состояние пользователя из Redis.
     *
     * @return array<string,mixed>|null
     */
    public static function get(int $userId): ?array
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return null;
        }

        $raw = $redis->get(self::key($userId));

        if ($raw === false || $raw === null) {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        try {
            /** @var array<string,mixed> $decoded */
            $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (JsonException $e) {
            Logger::error('Failed to decode telegram state: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function save(int $userId, array $data): void
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return;
        }
        $redis->set(self::key($userId), $data);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function update(int $userId, array $data): void
    {
        $current = self::get($userId) ?? [];
        foreach ($data as $k => $v) {
            $current[$k] = $v;
        }
        self::save($userId, $current);
    }

    public static function clear(int $userId): void
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return;
        }
        $redis->del(self::key($userId));
    }
}
