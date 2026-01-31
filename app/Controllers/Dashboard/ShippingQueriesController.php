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
 * Контроллер для просмотра запросов на доставку.
 */
final class ShippingQueriesController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу запросов на доставку.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Shipping',
        ];

        return View::render($res, 'dashboard/shipping/index.php', $data, 'layouts/main.php');
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

        $conds = [];
        $params = [];

        if (($p['from_user_id'] ?? '') !== '') {
            $conds[] = 'from_user_id = :from_user_id';
            $params['from_user_id'] = $p['from_user_id'];
        }
        if (($p['received_from'] ?? '') !== '') {
            $conds[] = 'received_at >= :received_from';
            $params['received_from'] = $p['received_from'];
        }
        if (($p['received_to'] ?? '') !== '') {
            $conds[] = 'received_at <= :received_to';
            $params['received_to'] = $p['received_to'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                shipping_query_id LIKE :search OR
                CAST(from_user_id AS CHAR) LIKE :search OR
                invoice_payload LIKE :search OR
                shipping_address LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT shipping_query_id, from_user_id, invoice_payload, shipping_address, received_at FROM tg_shipping_queries {$whereSql} ORDER BY received_at DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM tg_shipping_queries {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM tg_shipping_queries')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}
