<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Middleware для проверки CSRF-токена.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Проверяет CSRF-токен для небезопасных методов.
     *
     * @param Req $req HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после проверки
     */
    public function process(Req $req, Handler $handler): Res
    {
        if (in_array($req->getMethod(), ['POST','PUT','PATCH','DELETE'], true)) {
            $csrfName = $_ENV['CSRF_TOKEN_NAME'] ?? '_csrf_token';
            $body = (array)$req->getParsedBody();
            $formToken = (string)($body[$csrfName] ?? '');
            $cookieToken = (string)($req->getCookieParams()[$csrfName] ?? '');

            if ($formToken === '' || $cookieToken === '' || !hash_equals($formToken, $cookieToken)) {
                return Response::problem(new \Slim\Psr7\Response(), 403, 'CSRF check failed');
            }
        }
        return $handler->handle($req);
    }
}
