<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер авторизации панели управления.
 */
final class AuthController
{
    private PDO $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    /**
     * Отображает форму входа.
     *
     * @param Req $req HTTP-запрос
     * @param Res $res HTTP-ответ
     * @return Res Ответ с формой входа
     */
    public function showLogin(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Login',
            'error' => '',
        ];

        return View::render($res, 'dashboard/login.php', $data, 'layouts/centered.php');
    }

    /**
     * Выполняет вход пользователя.
     *
     * @param Req $req HTTP-запрос
     * @param Res $res HTTP-ответ
     * @return Res Ответ после входа
     */
    public function login(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $email = (string)($data['email'] ?? '');
        $pass = (string)($data['password'] ?? '');

        $stmt = $this->db->prepare('SELECT id, password FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($pass, $u['password'])) {
            $params = [
                'title' => 'Login',
                'error' => 'Invalid credentials',
            ];
            return View::render($res, 'dashboard/login.php', $params, 'layouts/centered.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];

        return $res->withHeader('Location', '/dashboard')->withStatus(302);
    }

    /**
     * Завершает сессию пользователя.
     *
     * @param Req $req HTTP-запрос
     * @param Res $res HTTP-ответ
     * @return Res Ответ после выхода
     */
    public function logout(Req $req, Res $res): Res
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $res->withHeader('Location', '/dashboard/login')->withStatus(302);
    }
}
