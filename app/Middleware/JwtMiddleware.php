<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * Middleware для проверки JWT токена.
 */
final class JwtMiddleware implements MiddlewareInterface
{
    /**
     * @param array $cfg Настройки JWT
     */
    public function __construct(private array $cfg)
    {
    }

    /**
     * Проверяет JWT и добавляет его данные в запрос.
     *
     * @param Req $req HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после проверки
     */
    public function process(Req $req, Handler $handler): Res
    {
        $auth = $req->getHeaderLine('Authorization');
        if (!preg_match('~^Bearer\s+(.+)$~i', $auth, $m)) {
            return Response::problem(new \Slim\Psr7\Response(), 401, 'Unauthorized');
        }
        try {
            $decoded = JWT::decode($m[1], new Key($this->cfg['secret'], $this->cfg['alg']));
            return $handler->handle($req->withAttribute('jwt', (array)$decoded));
        } catch (Throwable $e) {
            return Response::problem(new \Slim\Psr7\Response(), 401, 'Invalid token');
        }
    }
}
