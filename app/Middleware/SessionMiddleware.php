<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Middleware для запуска и проверки PHP-сессии.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    /** Имя cookie сессии */
    private string $name;

    /** Опции для session_start() */
    private array $options;

    /**
     * @param string $name    Имя cookie сессии
     * @param array  $options Опции для session_start()
     */
    public function __construct(string $name = 'SID', array $options = [])
    {
        $this->name = $name;
        $this->options = $options + [
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax',
        ];
    }

    /**
     * Запускает сессию и передаёт запрос дальше.
     *
     * @param Req $req HTTP-запрос
     * @param Handler $handler Следующий обработчик
     * @return Res Ответ после обработки
     */
    public function process(Req $req, Handler $handler): Res
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $sid = $req->getCookieParams()[$this->name] ?? null;
            if ($sid !== null && !preg_match('/^[A-Za-z0-9,-]{1,128}$/', $sid)) {
                session_id('');
            }

            session_name($this->name);
            session_start($this->options);
        }

        try {
            return $handler->handle($req);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }
    }
}
