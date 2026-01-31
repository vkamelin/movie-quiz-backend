<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\RedisHelper;
use App\Helpers\Response;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Middleware для ограничения количества запросов с использованием Sliding Window Algorithm.
 *
 * Использует Redis Sorted Sets для более точного распределения лимитов
 * и предотвращения burst-запросов на границах окон.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param array $cfg Параметры лимитирования
     */
    public function __construct(private array $cfg)
    {
    }

    /**
     * Применяет лимит запросов на основе IP или Telegram пользователя.
     *
     * @param Req $req HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после проверки
     */
    public function process(Req $req, Handler $handler): Res
    {
        $bucket = ($this->cfg['bucket'] ?? 'ip') === 'user' ? 'user' : 'ip';
        $limit = (int)($this->cfg['limit'] ?? 60);
        $windowSec = (int)($this->cfg['window_sec'] ?? 60);
        $prefix = (string)($this->cfg['redis_prefix'] ?? 'sw:');

        $server = $req->getServerParams();
        $clientIp = $server['REMOTE_ADDR'] ?? 'anon';
        $telegramUser = $req->getAttribute('telegramUser') ?? [];
        $userId = is_array($telegramUser) ? ($telegramUser['id'] ?? null) : null;

        $id = ($bucket === 'user' && $userId) ? ('u:' . (string)$userId) : ('ip:' . (string)$clientIp);
        $key = $prefix . $bucket . ':' . $id;

        $now = microtime(true);
        $windowStart = $now - $windowSec;

        try {
            $redis = RedisHelper::getInstance();

            // Добавляем текущий запрос в sorted set с timestamp как score
            $redis->zadd($key, [(string)$now => $now]);

            // Удаляем записи, вышедшие за пределы окна
            $redis->zremrangebyscore($key, '-inf', (string)$windowStart);

            // Подсчитываем количество запросов в текущем окне
            $count = $redis->zcard($key);
            if (!is_int($count)) {
                $count = 0;
            }

            // Устанавливаем TTL с небольшим запасом
            $redis->expire($key, $windowSec + 10);

            $remaining = max(0, $limit - $count);

            if ($count > $limit) {
                // Вычисляем время до следующего запроса
                $oldestResult = $redis->zrange($key, 0, 0, ['WITHSCORES' => true]);
                $oldestInWindow = 0.0;
                if (is_array($oldestResult) && !empty($oldestResult)) {
                    $values = array_values($oldestResult);
                    $oldestInWindow = (float)($values[0] ?? 0);
                }
                $retryAfter = $oldestInWindow > 0
                    ? (int)ceil($oldestInWindow + $windowSec - $now)
                    : $windowSec;

                $res429 = Response::problem(new \Slim\Psr7\Response(), 429, 'Too Many Requests', [
                    'retry_after' => $retryAfter,
                ]);

                return $res429
                    ->withHeader('Retry-After', (string)$retryAfter)
                    ->withHeader('X-RateLimit-Limit', (string)$limit)
                    ->withHeader('X-RateLimit-Remaining', '0')
                    ->withHeader('X-RateLimit-Window', (string)$windowSec);
            }

            $res = $handler->handle($req);
            return $res
                ->withHeader('X-RateLimit-Limit', (string)$limit)
                ->withHeader('X-RateLimit-Remaining', (string)$remaining)
                ->withHeader('X-RateLimit-Window', (string)$windowSec);
        } catch (\Throwable) {
            // Fail-open: если Redis недоступен, не блокируем запросы
            return $handler->handle($req);
        }
    }
}
