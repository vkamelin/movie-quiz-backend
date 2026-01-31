<?php

namespace App\Helpers;

use App\Config;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use RuntimeException;

/**
 * Обёртка над Monolog для централизованного логирования.
 */
/**
 * Обёртка над Monolog для единообразного и безопасного логирования.
 *
 * Инициализирует логгер, задаёт JSON-формат и ротацию файлов,
 * обогащает записи trace_id/request_id и маскирует PII-данные.
 */
class Logger
{
    private static ?MonologLogger $instance = null;
    private static ?string $requestId = null;

    /**
     * Устанавливает идентификатор текущего запроса.
     *
     * @param string|null $id Идентификатор запроса
     * @return void
     */
    /**
     * Устанавливает идентификатор запроса (request_id) для логов текущего контекста.
     *
     * @param string|null $id Идентификатор запроса (например, UUID)
     * @return void
     */
    public static function setRequestId(?string $id): void
    {
        self::$requestId = $id;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    /**
     * Маскирует потенциально чувствительные данные в массиве контекста.
     *
     * @param array<string, mixed> $data Входной контекст
     * @return array<string, mixed> Очищенный контекст с маскированными значениями
     */
    private static function redactPII(array $data): array
    {
        $pii = ['password', 'email', 'phone', 'token', 'secret', 'ssn', 'credit_card'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactPII($value);
                continue;
            }

            if (is_string($key) && in_array(strtolower($key), $pii, true)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }

    /**
     * Возвращает экземпляр логгера.
     *
     * @return MonologLogger Экземпляр Monolog
     */
    /**
     * Возвращает настроенный экземпляр Monolog.
     *
     * Создаёт логгер при первом обращении, настраивает ротацию файлов,
     * JSON-формат и процессоры (UID, request_id, маскировка PII).
     *
     * @return MonologLogger Экземпляр Monolog
     */
    public static function get(): MonologLogger
    {
        if (self::$instance === null) {
            $logDir = __DIR__ . '/../../storage/logs/';
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $logDir));
                }
            }
            try {
                $config = Config::getInstance();
                $channel = (string) $config->get('LOG_CHANNEL', 'app');
                $levelName = (string) $config->get('LOG_LEVEL', 'INFO');
                if ($levelName === '') {
                    $levelName = 'INFO';
                }
            } catch (\Throwable) {
                $channel = 'app';
                $levelName = 'INFO';
            }

            $logger = new MonologLogger($channel);

            // Use daily rotating logs. Each log file will have a date suffix.
            // Keep logs for 30 days, rotate daily. Log level configured via config
            $handler = new RotatingFileHandler(
                $logDir . 'app.log',
                30,
                MonologLogger::toMonologLevel($levelName)
            );
            $formatter = new JsonFormatter();
            $formatter->setMaxNormalizeDepth(20);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            $logger->pushProcessor(new PsrLogMessageProcessor());
            $logger->pushProcessor(new UidProcessor());
            $logger->pushProcessor(
                /** @param array<string, mixed> $record @return array<string, mixed> */
                function (array $record): array {
                    if (isset($record['extra']['uid'])) {
                        $record['extra']['trace_id'] = $record['extra']['uid'];
                        unset($record['extra']['uid']);
                    }
                    if (self::$requestId !== null) {
                        $record['extra']['request_id'] = self::$requestId;
                    }
                    $record['context'] = self::redactPII($record['context']);
                    $record['extra'] = self::redactPII($record['extra']);
                    return $record;
                }
            );

            self::$instance = $logger;
        }

        return self::$instance;
    }

    /**
     * Записывает сообщение уровня info.
     *
     * @param string $message Текст сообщения
     * @param array<string, mixed> $context Дополнительный контекст
     * @return void
     */
    /**
     * Пишет сообщение уровня info.
     *
     * @param string $message Текст сообщения
     * @param array<string,mixed> $context Дополнительный контекст
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::get()->info($message, $context);
    }

    /**
     * Записывает сообщение уровня error.
     *
     * @param string $message Текст сообщения
     * @param array<string, mixed> $context Дополнительный контекст
     * @return void
     */
    /**
     * Пишет сообщение уровня error. Если не передан trace — добавляет бэктрейс автоматически.
     *
     * @param string $message Текст сообщения
     * @param array<string,mixed> $context Дополнительный контекст
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        if (empty($context['trace'])) {
            $trace = debug_backtrace();
            $context['trace'] = $trace;
        }

        self::get()->error($message, $context);
    }

    /**
     * Записывает сообщение уровня debug.
     *
     * @param string $message Текст сообщения
     * @param array<string, mixed> $context Дополнительный контекст
     * @return void
     */
    /**
     * Пишет сообщение уровня debug. Если не передан trace — добавляет бэктрейс автоматически.
     *
     * @param string $message Текст сообщения
     * @param array<string,mixed> $context Дополнительный контекст
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        if (empty($context['trace'])) {
            $trace = debug_backtrace();
            $context['trace'] = $trace;
        }

        self::get()->debug($message, $context);
    }

    /**
     * Записывает сообщение уровня warning.
     *
     * @param string $message Текст сообщения
     * @param array<string, mixed> $context Дополнительный контекст
     * @return void
     */
    /**
     * Пишет сообщение уровня warning.
     *
     * @param string $message Текст сообщения
     * @param array<string,mixed> $context Дополнительный контекст
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::get()->warning($message, $context);
    }

    /**
     * Записывает сообщение уровня critical.
     *
     * @param string $message Текст сообщения
     * @param array<string, mixed> $context Дополнительный контекст
     * @return void
     */
    /**
     * Пишет сообщение уровня critical. Если не передан trace — добавляет бэктрейс автоматически.
     *
     * @param string $message Текст сообщения
     * @param array<string,mixed> $context Дополнительный контекст
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        if (empty($context['trace'])) {
            $trace = debug_backtrace();
            $context['trace'] = $trace;
        }

        self::get()->critical($message, $context);
    }
}
