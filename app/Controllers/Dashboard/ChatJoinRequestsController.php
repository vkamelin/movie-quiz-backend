<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Response;
use App\Helpers\View;
use Longman\TelegramBot\Request;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер управления заявками на вступление в чат.
 */
final class ChatJoinRequestsController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу заявок.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Chat Join Requests',
        ];

        return View::render($res, 'dashboard/join-requests/index.php', $data, 'layouts/main.php');
    }

    /**
     * Возвращает данные для DataTables с серверной пагинацией и фильтрами.
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

        $conds = [];
        $params = [];

        if (($p['status'] ?? '') !== '') {
            $conds[] = 'c.status = :status';
            $params['status'] = $p['status'];
        }
        if (($p['chat_id'] ?? '') !== '') {
            $conds[] = 'c.chat_id = :chat_id';
            $params['chat_id'] = $p['chat_id'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                CAST(c.chat_id AS CHAR) LIKE :search OR
                CAST(c.user_id AS CHAR) LIKE :search OR
                tu.username LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT c.chat_id, c.user_id, tu.username, c.bio, c.invite_link, c.requested_at, c.status, c.decided_at, c.decided_by FROM chat_join_requests c LEFT JOIN telegram_users tu ON tu.user_id = c.user_id {$whereSql} ORDER BY c.requested_at DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM chat_join_requests c LEFT JOIN telegram_users tu ON tu.user_id = c.user_id {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM chat_join_requests')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Отображает карточку заявки.
     */
    public function view(Req $req, Res $res, array $args): Res
    {
        $chatId = (int)($args['chat_id'] ?? 0);
        $userId = (int)($args['user_id'] ?? 0);
        $stmt = $this->db->prepare('SELECT c.*, tu.username, tu.first_name, tu.last_name FROM chat_join_requests c LEFT JOIN telegram_users tu ON tu.user_id = c.user_id WHERE c.chat_id = :chat_id AND c.user_id = :user_id');
        $stmt->execute(['chat_id' => $chatId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return $res->withStatus(404);
        }

        $data = [
            'title' => 'Join Request',
            'request' => $row,
        ];

        return View::render($res, 'dashboard/join-requests/view.php', $data, 'layouts/main.php');
    }

    /**
     * Одобряет заявку на вступление.
     */
    public function approve(Req $req, Res $res, array $args): Res
    {
        $chatId = (int)($args['chat_id'] ?? 0);
        $userId = (int)($args['user_id'] ?? 0);
        Request::approveChatJoinRequest(['chat_id' => $chatId, 'user_id' => $userId]);
        $stmt = $this->db->prepare('UPDATE chat_join_requests SET status = :status, decided_at = CURRENT_TIMESTAMP, decided_by = :decided_by WHERE chat_id = :chat_id AND user_id = :user_id');
        $stmt->execute([
            'status' => 'approved',
            'decided_by' => $_SESSION['user_id'] ?? null,
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        $stmt2 = $this->db->prepare('INSERT INTO chat_members (chat_id, user_id, role, state) VALUES (:chat_id, :user_id, :role, :state) ON DUPLICATE KEY UPDATE role = VALUES(role), state = VALUES(state)');
        $stmt2->execute([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'role' => 'member',
            'state' => 'approved',
        ]);

        return $res->withHeader('Location', '/dashboard/join-requests')->withStatus(302);
    }

    /**
     * Отклоняет заявку на вступление.
     */
    public function decline(Req $req, Res $res, array $args): Res
    {
        $chatId = (int)($args['chat_id'] ?? 0);
        $userId = (int)($args['user_id'] ?? 0);
        Request::declineChatJoinRequest(['chat_id' => $chatId, 'user_id' => $userId]);
        $stmt = $this->db->prepare('UPDATE chat_join_requests SET status = :status, decided_at = CURRENT_TIMESTAMP, decided_by = :decided_by WHERE chat_id = :chat_id AND user_id = :user_id');
        $stmt->execute([
            'status' => 'declined',
            'decided_by' => $_SESSION['user_id'] ?? null,
            'chat_id' => $chatId,
            'user_id' => $userId,
        ]);

        $stmt2 = $this->db->prepare('INSERT INTO chat_members (chat_id, user_id, role, state) VALUES (:chat_id, :user_id, NULL, :state) ON DUPLICATE KEY UPDATE role = VALUES(role), state = VALUES(state)');
        $stmt2->execute([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'state' => 'declined',
        ]);

        return $res->withHeader('Location', '/dashboard/join-requests')->withStatus(302);
    }
}
