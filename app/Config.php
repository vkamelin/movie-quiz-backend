<?php

declare(strict_types=1);

namespace App;

/**
 * Класс конфигурации приложения.
 *
 * Позволяет получать значения из конфигурационного массива и переменных
 * окружения. Значения из окружения имеют приоритет над значениями из
 * конфигурационного файла.
 */
final class Config
{
    /**
     * Loaded configuration values.
     *
     * @var array<string, mixed>
     */
    /**
     * Загруженные настройки приложения.
     *
     * @var array<string,mixed>
     */
    private array $data;

    /** Единственный инстанс (Singleton). */
    private static ?self $instance = null;

    /**
     * @phpstan-ignore-next-line
     */
    private function __construct()
    {
        /** @var array<string, mixed> $config */
        $config = require __DIR__ . '/Config/config.php';
        $this->data = $config;
    }

    /**
     * Возвращает singleton-инстанс конфигурации.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Возвращает значение конфигурации по ключу.
     *
     * Переменные окружения имеют приоритет над значениями из файла
     * конфигурации. Если ключ отсутствует, возвращается значение по умолчанию.
     *
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed Найденное значение или $default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        return $this->data[$key] ?? $default;
    }
}
