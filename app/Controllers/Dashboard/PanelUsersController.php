<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Flash;
use App\Helpers\Response;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер управления пользователями панели.
 */
final class PanelUsersController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает список пользователей панели.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Panel Users',
        ];

        return View::render($res, 'dashboard/users/index.php', $data, 'layouts/main.php');
    }

    /**
     * Возвращает данные для DataTables.
     */
    public function data(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $start = max(0, (int)($p['start'] ?? 0));
        $length = (int)($p['length'] ?? 10);
        $draw = (int)($p['draw'] ?? 0);
        if ($length === -1) {
            $start = 0;
        }

        $params = [];
        $searchValue = $p['search']['value'] ?? '';
        $whereSql = '';
        if ($searchValue !== '') {
            $whereSql = 'WHERE CAST(id AS CHAR) LIKE :search OR email LIKE :search OR CAST(telegram_user_id AS CHAR) LIKE :search';
            $params['search'] = '%' . $searchValue . '%';
        }

        $sql = "SELECT id, email, telegram_user_id, created_at, updated_at FROM users {$whereSql} ORDER BY id DESC";
        if ($length > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        if ($length > 0) {
            $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Отображает форму создания пользователя.
     */
    public function create(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Create User',
            'user' => ['email' => '', 'telegram_user_id' => ''],
            'errors' => [],
        ];

        return View::render($res, 'dashboard/users/form.php', $data, 'layouts/main.php');
    }

    /**
     * Сохраняет нового пользователя.
     */
    public function store(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $email = trim((string)($data['email'] ?? ''));
        $telegramId = trim((string)($data['telegram_user_id'] ?? ''));

        $errors = [];
        if ($email === '') {
            $errors[] = 'Email is required';
        }

        if (!empty($errors)) {
            $params = [
                'title' => 'Create User',
                'user' => ['email' => $email, 'telegram_user_id' => $telegramId],
                'errors' => $errors,
            ];
            return View::render($res, 'dashboard/users/form.php', $params, 'layouts/main.php');
        }

        $stmt = $this->db->prepare('INSERT INTO users (email, telegram_user_id) VALUES (:email, :telegram_user_id)');
        $stmt->execute([
            'email' => $email,
            'telegram_user_id' => $telegramId !== '' ? $telegramId : null,
        ]);

        Flash::add('success', 'User created');
        return $res->withHeader('Location', '/dashboard/users')->withStatus(302);
    }

    /**
     * Отображает форму редактирования пользователя.
     */
    public function edit(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT id, email, telegram_user_id, created_at, updated_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return $res->withStatus(404);
        }

        $data = [
            'title' => 'Edit User',
            'user' => $user,
            'errors' => [],
        ];

        return View::render($res, 'dashboard/users/form.php', $data, 'layouts/main.php');
    }

    /**
     * Обновляет данные пользователя.
     */
    public function update(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT id FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            return $res->withStatus(404);
        }

        $data = (array)$req->getParsedBody();
        $email = trim((string)($data['email'] ?? ''));
        $telegramId = trim((string)($data['telegram_user_id'] ?? ''));

        $errors = [];
        if ($email === '') {
            $errors[] = 'Email is required';
        }

        if (!empty($errors)) {
            $params = [
                'title' => 'Edit User',
                'user' => ['id' => $id, 'email' => $email, 'telegram_user_id' => $telegramId],
                'errors' => $errors,
            ];
            return View::render($res, 'dashboard/users/form.php', $params, 'layouts/main.php');
        }

        $stmt = $this->db->prepare('UPDATE users SET email = :email, telegram_user_id = :telegram_user_id WHERE id = :id');
        $stmt->execute([
            'email' => $email,
            'telegram_user_id' => $telegramId !== '' ? $telegramId : null,
            'id' => $id,
        ]);

        Flash::add('success', 'User updated');
        return $res->withHeader('Location', '/dashboard/users')->withStatus(302);
    }
}
