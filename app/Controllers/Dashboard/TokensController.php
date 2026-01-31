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
 * Контроллер для управления refresh-токенами.
 */
final class TokensController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу токенов.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Tokens',
        ];

        return View::render($res, 'dashboard/tokens/index.php', $data, 'layouts/main.php');
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

        if (($p['revoked'] ?? '') !== '') {
            $conds[] = 'revoked = :revoked';
            $params['revoked'] = (int)$p['revoked'];
        }
        if (($p['period'] ?? '') !== '') {
            $parts = explode(',', (string)$p['period']);
            if (count($parts) === 2) {
                $from = strtotime($parts[0]);
                $to = strtotime($parts[1]);
                if ($from && $to) {
                    $conds[] = 'expires_at BETWEEN :expires_from AND :expires_to';
                    $params['expires_from'] = $from;
                    $params['expires_to'] = $to;
                }
            }
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(CAST(id AS CHAR) LIKE :search OR CAST(user_id AS CHAR) LIKE :search OR jti LIKE :search)';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT id, user_id, jti, FROM_UNIXTIME(expires_at) AS expires_at, revoked, created_at, updated_at FROM refresh_tokens {$whereSql} ORDER BY id DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM refresh_tokens {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM refresh_tokens')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Помечает токен как отозванный.
     */
    public function revoke(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('UPDATE refresh_tokens SET revoked = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $res->withHeader('Location', '/dashboard/tokens')->withStatus(302);
    }
}
