<?php

declare(strict_types=1);

namespace App\Helpers;

final class SchemaLoader
{
    /** @var array<string,array> */
    private static array $cache = [];

    /**
     * Загружает JSON Schema из каталога app/Schemas по имени (name.schema.json).
     *
     * @return array<string,mixed>
     */
    public static function load(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        $path = dirname(__DIR__) . '/Schemas/' . $name . '.schema.json';
        if (!is_file($path)) {
            throw new \RuntimeException('Schema not found: ' . $name);
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException('Schema read error: ' . $name);
        }
        $schema = json_decode($raw, true);
        if (!is_array($schema)) {
            throw new \RuntimeException('Schema decode error: ' . $name);
        }
        return self::$cache[$name] = $schema;
    }
}
