<?php

declare(strict_types=1);

namespace App;

/**
 * Минимальная фасад-обёртка для телеметрии.
 *
 * Все методы становятся no-op, если телеметрия отключена или отсутствуют
 * необходимые библиотеки. Включение: переменная окружения `TELEMETRY_ENABLED=true`
 * и установка соответствующих пакетов.
 */
class Telemetry
{
    private static ?bool $enabled = null;

    /**
     * Проверяет, включена ли телеметрия и доступны ли библиотеки.
     */
    public static function enabled(): bool
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }

        $envEnabled = filter_var($_ENV['TELEMETRY_ENABLED'] ?? false, FILTER_VALIDATE_BOOL);
        $hasLibrary = class_exists('OpenTelemetry\\API\\Globals')
            || class_exists('OpenTelemetry\\API\\Metrics\\MeterProvider')
            || class_exists('OpenTelemetry\\API\\Trace\\TracerProvider');

        self::$enabled = $envEnabled && $hasLibrary;
        return self::$enabled;
    }

    /**
     * Инкрементирует счётчик успешно отправленных сообщений Telegram.
     */
    public static function incrementTelegramSent(): void
    {
        if (!self::enabled()) {
            return;
        }
        // integrate with metrics backend
    }

    /**
     * Регистрирует неуспешную отправку в Telegram.
     *
     * @param string $reason Причина неудачи
     */
    public static function recordTelegramSendFailure(string $reason): void
    {
        if (!self::enabled()) {
            return;
        }
        // integrate with metrics backend
    }

    /**
     * Обновляет метрику размера очереди Telegram.
     *
     * @param int $size Текущий размер очереди
     */
    public static function setTelegramQueueSize(int $size): void
    {
        if (!self::enabled()) {
            return;
        }
        // gauge update
    }

    /**
     * Обновляет метрику размера очереди DLQ.
     *
     * @param int $size Текущий размер очереди DLQ
     */
    public static function setDlqSize(int $size): void
    {
        if (!self::enabled()) {
            return;
        }
        // gauge update
    }

    /**
     * Устанавливает состояние circuit breaker для GPT.
     *
     * @param string $state Текущее состояние
     */
    public static function setGptBreakerState(string $state): void
    {
        if (!self::enabled()) {
            return;
        }
        // gauge update
    }

    /**
     * Наблюдает время ответа GPT.
     *
     * @param float $seconds Длительность в секундах
     */
    public static function observeGptResponseTime(float $seconds): void
    {
        if (!self::enabled()) {
            return;
        }
        // histogram/summary update
    }
}
