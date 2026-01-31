<?php

declare(strict_types=1);

namespace App\Helpers;

use JsonException;
use RuntimeException;

class FlowState
{
    private const string PREFIX = 'flow:';

    private static function key(string $id): string
    {
        return self::PREFIX . $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return null;
        }

        $raw = $redis->get(self::key($id));

        if ($raw === false || $raw === null) {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        try {
            return json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Logger::error('Failed to decode flow state: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function save(string $id, array $data): void
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return;
        }

        $redis->set(self::key($id), $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(string $id, array $data): void
    {
        $current = self::get($id) ?? [];

        foreach ($data as $key => $value) {
            $current[$key] = $value;
        }

        self::save($id, $current);
    }

    public static function clear(string $id): void
    {
        try {
            $redis = RedisHelper::getInstance();
        } catch (RuntimeException $e) {
            Logger::error('Redis connection failed: ' . $e->getMessage());
            return;
        }

        $redis->del(self::key($id));
    }
}
