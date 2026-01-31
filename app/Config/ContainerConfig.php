<?php

declare(strict_types=1);

namespace App\Config;

use App\Helpers\Database;
use App\Middleware\JwtMiddleware;
use App\Middleware\RateLimitMiddleware;
use DI\Container;
use PDO;

/**
 * Конфигурация DI-контейнера.
 *
 * Определяет сервисы приложения. Контроллеры НЕ регистрируются вручную —
 * PHP-DI использует автовайринг и автоматически создаёт их через рефлексию.
 */
final class ContainerConfig
{
    /**
     * Возвращает массив конфигурации для DI-контейнера.
     *
     * @return array<string, mixed>
     */
    public static function getDefinitions(): array
    {
        return [
            // === Сервисы (требуют явной инициализации) ===

            // PDO как синглтон
            PDO::class => function (Container $container): PDO {
                $config = $container->get('config');
                $dsn = $config['db']['dsn'];
                $user = $config['db']['user'];
                $pass = $config['db']['pass'];
                $opts = $config['db']['opts'];

                if (!$dsn || !$user || !$pass) {
                    throw new \RuntimeException('Database configuration is incomplete');
                }

                return new PDO($dsn, $user, $pass, $opts);
            },

            // Database helper как синглтон
            Database::class => function (): PDO {
                return Database::getInstance();
            },

            // RateLimitMiddleware с конфигурацией
            RateLimitMiddleware::class => function (Container $container): RateLimitMiddleware {
                $config = $container->get('config');
                return new RateLimitMiddleware($config['rate_limit'] ?? [
                    'bucket' => 'ip',
                    'limit' => 60,
                    'window_sec' => 60,
                    'redis_prefix' => 'rl:',
                ]);
            },

            // JwtMiddleware с конфигурацией
            JwtMiddleware::class => function (Container $container): JwtMiddleware {
                $jwtConfig = $container->get('jwt_config');
                return new JwtMiddleware([
                    'secret' => $jwtConfig['secret'] ?? '',
                    'alg' => $jwtConfig['alg'] ?? 'HS256',
                ]);
            },

            // TelegramInitDataMiddleware с токеном бота
            TelegramInitDataMiddleware::class => function (Container $container): TelegramInitDataMiddleware {
                $config = $container->get('config');
                return new TelegramInitDataMiddleware($config['bot_token'] ?? '');
            },
        ];
    }
}
