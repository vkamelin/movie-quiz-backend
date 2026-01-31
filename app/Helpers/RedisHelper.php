<?php

declare(strict_types=1);

namespace App\Helpers;

use Redis;
use RedisException;

/**
 * Helper class for Redis operations.
 *
 * @package App\Classes
 * @author
 * @version 1.1
 */
class RedisHelper
{
    // Telegram-related keys
    public const string REDIS_MESSAGE_KEY = 'telegram:message';
    public const string REDIS_MESSAGES_QUEUE_KEY = 'telegram:message:queue';
    public const string REDIS_LONGPOLLING_OFFSET_KEY = 'telegram:longpolling:offset';
    public const string REDIS_USER_KEY = 'telegram:user';

    /** @var Redis|null */
    private static ?Redis $instance = null;

    /**
     * Get a singleton Redis instance.
     *
     * @return Redis
     * @throws RedisException
     */
    public static function getInstance(): Redis
    {
        if (self::$instance instanceof Redis) {
            return self::$instance;
        }

        $redis = new Redis();

        // Connect via socket or TCP
        $redis->pconnect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT'], 1.0);

        // Apply key prefix before selecting the DB
        $prefix = (string)$_ENV['REDIS_PREFIX'];
        if ($prefix !== '') {
            $redis->setOption(Redis::OPT_PREFIX, $prefix);
        }

        // General options
        $redis->setOption(Redis::OPT_MAX_RETRIES, 3);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        $redis->setOption(Redis::OPT_READ_TIMEOUT, 1.0);
        $redis->setOption(Redis::OPT_TCP_KEEPALIVE, true);
        $redis->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_NONE);
        $redis->setOption(Redis::OPT_REPLY_LITERAL, false);
        $redis->setOption(Redis::OPT_COMPRESSION_LEVEL, 0);
        $redis->setOption(Redis::OPT_BACKOFF_ALGORITHM, Redis::BACKOFF_ALGORITHM_EXPONENTIAL);
        $redis->setOption(Redis::OPT_BACKOFF_BASE, 100);
        $redis->setOption(Redis::OPT_BACKOFF_CAP, 1000);

        // Select the configured database
        $dbIndex = (int)$_ENV['REDIS_DB'];
        $redis->select($dbIndex);

        return self::$instance = $redis;
    }

    /**
     * Store or update arbitrary user data in a Redis hash.
     *
     * @param int   $userId
     * @param array $data Associative array of field => value
     *
     * @return void
     * @throws RedisException
     */
    public static function addUserData(int $userId, array $data): void
    {
        $redis = self::getInstance();
        $key = self::REDIS_USER_KEY . ":{$userId}";

        // Use HMSET to write all fields in one command
        $redis->hMSet($key, $data);
    }

    /**
     * Remove an entire user hash from Redis.
     *
     * @param int $userId
     *
     * @return int  Number of keys deleted (0 or 1)
     * @throws RedisException
     */
    public static function deleteUser(int $userId): int
    {
        $redis = self::getInstance();
        $key = self::REDIS_USER_KEY . ":{$userId}";

        return (int)$redis->del($key);
    }

    /**
     * Remove specific fields from a user hash.
     *
     * @param int   $userId
     * @param array $fields List of hash fields to delete
     *
     * @return int  Number of fields removed
     * @throws RedisException
     */
    public static function deleteUserData(int $userId, array $fields): int
    {
        $redis = self::getInstance();
        $key = self::REDIS_USER_KEY . ":{$userId}";

        return (int)$redis->hDel($key, ...$fields);
    }

    /**
     * Retrieve all data for a user hash.
     *
     * @param int $userId
     *
     * @return array  Associative array of field => value, or empty if none
     * @throws RedisException
     */
    public static function getUserData(int $userId): array
    {
        $redis = self::getInstance();
        $key = self::REDIS_USER_KEY . ":{$userId}";

        $data = $redis->hGetAll($key);
        return is_array($data) ? $data : [];
    }
}
