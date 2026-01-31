<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Middleware для проверки авторизации пользователей панели.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет наличие user_id в сессии.
     * Если пользователь не авторизован, перенаправляет на /dashboard/login.
     *
     * @param Req $req HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после проверки
     */
    public function process(Req $req, Handler $handler): Res
    {
        $uri = $req->getUri()->getPath();

        // Исключаем маршруты логина/логаута из проверки
        if ($uri === '/dashboard/login' || $uri === '/dashboard/logout') {
            return $handler->handle($req);
        }

        if (empty($_SESSION['user_id'])) {
            $res = new \Slim\Psr7\Response();
            return $res->withHeader('Location', '/dashboard/login')->withStatus(302);
        }

        return $handler->handle($req);
    }
}
