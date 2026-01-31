<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Config;

final class RedisKeyHelper
{
    /**
     * Ключ для Redis
     *
     * @param string      $context
     * @param string      $entity
     * @param string|null $id
     *
     * @return string
     */
    public static function key(string $context, string $entity, ?string $id = null): string
    {
        $config = Config::getInstance();
        $env = (string)$config->get('APP_ENV', 'dev');
        $parts = [$env, $context, $entity]; // var_dump($id); exit;
        if ($id !== null && $id !== '') {
            $parts[] = $id;
        }
        return implode(':', $parts);
    }

    /**
     * Ключ для записи в Redis
     *
     * @param string      $context
     * @param string      $entity
     * @param string|null $id
     *
     * @return string
     */
    public static function set(string $context, string $entity, ?string $id = null): string
    {
        return self::key($context, $entity, $id = null);
    }

    /**
     * Ключ для чтения из Redis
     *
     * @param string      $context
     * @param string      $entity
     * @param string|null $id
     *
     * @return string
     */
    public static function get(string $context, string $entity, ?string $id = null): string
    {
        return self::key($context, $entity, $id);
    }
}
