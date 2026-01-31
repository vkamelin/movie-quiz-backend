<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

final class UtmController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();

        // Defaults: last 30 days
        $now = new \DateTimeImmutable('now');
        $defaultFrom = $now->modify('-30 days')->format('Y-m-d\TH:i');
        $defaultTo = $now->format('Y-m-d\TH:i');

        $from = (string)($p['from'] ?? $defaultFrom);
        $to = (string)($p['to'] ?? $defaultTo);

        $conds = [];
        $params = [];
        if ($from !== '') {
            $conds[] = 'pc.received_at >= :from';
            $params['from'] = $from;
        }
        if ($to !== '') {
            $conds[] = 'pc.received_at <= :to';
            $params['to'] = $to;
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        // Aggregate by full UTM string stored on user
        $sql = "SELECT COALESCE(NULLIF(tu.utm, ''), '(no utm)') AS utm,
                       SUM(pc.total_amount) AS total
                FROM tg_pre_checkout pc
                LEFT JOIN telegram_users tu ON tu.user_id = pc.from_user_id
                {$whereSql}
                GROUP BY utm
                ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        $utms = $stmt->fetchAll() ?: [];

        $totalSql = "SELECT SUM(pc.total_amount) AS total
                     FROM tg_pre_checkout pc
                     {$whereSql}";
        $tStmt = $this->db->prepare($totalSql);
        foreach ($params as $k => $v) {
            $tStmt->bindValue(':' . $k, $v);
        }
        $tStmt->execute();
        $grandTotal = (int)($tStmt->fetchColumn() ?: 0);

        return View::render($res, 'dashboard/utm/index.php', [
            'title' => 'UTM',
            'utms' => $utms,
            'from' => $from,
            'to' => $to,
            'grandTotal' => $grandTotal,
        ], 'layouts/main.php');
    }
}
