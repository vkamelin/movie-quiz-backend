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
 * Контроллер управления участниками чатов.
 */
final class ChatMembersController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Chat Members',
        ];

        return View::render($res, 'dashboard/chat-members/index.php', $data, 'layouts/main.php');
    }

    public function data(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $start = max(0, (int)($p['start'] ?? 0));
        $length = max(10, (int)($p['length'] ?? 10));
        $draw = (int)($p['draw'] ?? 0);

        $conds = [];
        $params = [];

        if (($p['state'] ?? '') !== '') {
            $conds[] = 'cm.state = :state';
            $params['state'] = $p['state'];
        }
        if (($p['chat_id'] ?? '') !== '') {
            $conds[] = 'cm.chat_id = :chat_id';
            $params['chat_id'] = $p['chat_id'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                CAST(cm.chat_id AS CHAR) LIKE :search OR
                CAST(cm.user_id AS CHAR) LIKE :search OR
                tu.username LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT cm.chat_id, cm.user_id, tu.username, cm.role, cm.state FROM chat_members cm LEFT JOIN telegram_users tu ON tu.user_id = cm.user_id {$whereSql} ORDER BY cm.chat_id, cm.user_id";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM chat_members cm LEFT JOIN telegram_users tu ON tu.user_id = cm.user_id {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM chat_members')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}
