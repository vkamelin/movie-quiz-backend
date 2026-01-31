<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Response;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер для управления группами пользователей Telegram.
 */
final class TgGroupsController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Список групп.
     */
    public function index(Req $req, Res $res): Res
    {
        $stmt = $this->db->query(
            'SELECT g.id, g.name, COUNT(ugu.user_id) AS members '
            . 'FROM telegram_user_groups g '
            . 'LEFT JOIN telegram_user_group_user ugu ON ugu.group_id = g.id '
            . 'GROUP BY g.id, g.name ORDER BY g.id DESC'
        );
        $groups = $stmt->fetchAll();

        $data = [
            'title' => 'Telegram Groups',
            'groups' => $groups,
        ];

        return View::render($res, 'dashboard/tg-groups/index.php', $data, 'layouts/main.php');
    }

    /**
     * Создание новой группы.
     */
    public function store(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $name = trim((string)($p['name'] ?? ''));
        if ($name !== '') {
            $stmt = $this->db->prepare('INSERT INTO telegram_user_groups (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);
        }
        return $res->withHeader('Location', '/dashboard/tg-groups')->withStatus(302);
    }

    /**
     * Просмотр и редактирование группы.
     */
    public function view(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        if ($req->getMethod() === 'POST') {
            $p = (array)$req->getParsedBody();
            $name = trim((string)($p['name'] ?? ''));
            if ($name !== '') {
                $stmt = $this->db->prepare('UPDATE telegram_user_groups SET name = :name WHERE id = :id');
                $stmt->execute(['name' => $name, 'id' => $id]);
            }
            return $res->withHeader('Location', '/dashboard/tg-groups/' . $id)->withStatus(302);
        }

        $stmt = $this->db->prepare('SELECT * FROM telegram_user_groups WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $group = $stmt->fetch();
        if (!$group) {
            return $res->withStatus(404);
        }

        $membersStmt = $this->db->prepare(
            'SELECT tu.id, tu.user_id, tu.username '
            . 'FROM telegram_user_group_user ugu '
            . 'JOIN telegram_users tu ON tu.id = ugu.user_id '
            . 'WHERE ugu.group_id = :id ORDER BY tu.id DESC'
        );
        $membersStmt->execute(['id' => $id]);
        $members = $membersStmt->fetchAll();

        $data = [
            'title' => 'Group ' . $group['name'],
            'group' => $group,
            'members' => $members,
        ];

        return View::render($res, 'dashboard/tg-groups/view.php', $data, 'layouts/main.php');
    }

    /**
     * Добавление пользователя в группу.
     */
    public function addUser(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $p = (array)$req->getParsedBody();
        $userTelegramId = (int)($p['user_id'] ?? 0);
        $user = null;
        if ($userTelegramId > 0) {
            $select = $this->db->prepare('SELECT id, user_id, username FROM telegram_users WHERE user_id = :uid');
            $select->execute(['uid' => $userTelegramId]);
            $user = $select->fetch();
            if ($user) {
                $stmt = $this->db->prepare(
                    'INSERT IGNORE INTO telegram_user_group_user (group_id, user_id) VALUES (:gid, :uid)'
                );
                $stmt->execute(['gid' => $id, 'uid' => $user['id']]);
            }
        }
        if ($req->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return Response::json($res, 200, ['user' => $user]);
        }
        return $res->withHeader('Location', '/dashboard/tg-groups/' . $id)->withStatus(302);
    }

    /**
     * Удаление пользователя из группы.
     */
    public function removeUser(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $p = (array)$req->getParsedBody();
        $userId = (int)($p['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $this->db->prepare(
                'DELETE FROM telegram_user_group_user WHERE group_id = :gid AND user_id = :uid'
            );
            $stmt->execute(['gid' => $id, 'uid' => $userId]);
        }
        if ($req->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return Response::json($res, 200, ['status' => 'ok']);
        }
        return $res->withHeader('Location', '/dashboard/tg-groups/' . $id)->withStatus(302);
    }
}
