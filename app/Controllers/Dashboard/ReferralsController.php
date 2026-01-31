<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Response;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

final class ReferralsController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(Req $req, Res $res): Res
    {
        // Simple metrics for header cards
        $total = (int)$this->db->query('SELECT COUNT(*) FROM referrals')->fetchColumn();
        $inviters = (int)$this->db->query('SELECT COUNT(DISTINCT inviter_user_id) FROM referrals')->fetchColumn();
        $invitees = (int)$this->db->query('SELECT COUNT(DISTINCT invitee_user_id) FROM referrals')->fetchColumn();
        $avgPerInviter = $inviters > 0 ? round($total / $inviters, 2) : 0.0;

        return View::render($res, 'dashboard/referrals/index.php', [
            'title' => 'Рефералы',
            'metrics' => [
                'total' => $total,
                'inviters' => $inviters,
                'invitees' => $invitees,
                'avg_per_inviter' => $avgPerInviter,
            ],
        ], 'layouts/main.php');
    }

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

        if (($p['inviter_user_id'] ?? '') !== '') {
            $conds[] = 'r.inviter_user_id = :inviter_user_id';
            $params['inviter_user_id'] = (int)$p['inviter_user_id'];
        }
        if (($p['invitee_user_id'] ?? '') !== '') {
            $conds[] = 'r.invitee_user_id = :invitee_user_id';
            $params['invitee_user_id'] = (int)$p['invitee_user_id'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                CAST(r.inviter_user_id AS CHAR) LIKE :search
                OR CAST(r.invitee_user_id AS CHAR) LIKE :search
                OR inv.username LIKE :search
                OR ine.username LIKE :search
                OR r.via_code LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT r.id, r.created_at,
                       r.inviter_user_id, inv.username AS inviter_username,
                       r.invitee_user_id, ine.username AS invitee_username,
                       r.via_code
                FROM referrals r
                LEFT JOIN telegram_users inv ON inv.user_id = r.inviter_user_id
                LEFT JOIN telegram_users ine ON ine.user_id = r.invitee_user_id
                {$whereSql}
                ORDER BY r.id DESC";
        if ($length > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        if ($length > 0) {
            $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $countStmt = $this->db->prepare("SELECT COUNT(*)
            FROM referrals r
            LEFT JOIN telegram_users inv ON inv.user_id = r.inviter_user_id
            LEFT JOIN telegram_users ine ON ine.user_id = r.invitee_user_id
            {$whereSql}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue(':' . $k, $v);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();
        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM referrals')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Сводка по пригласившим (группировка по inviter_user_id)
     */
    public function grouped(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $start = max(0, (int)($p['start'] ?? 0));
        $length = (int)($p['length'] ?? 10);
        $draw = (int)($p['draw'] ?? 0);
        if ($length === -1) {
            $start = 0;
        }

        $conds = ['r.inviter_user_id IS NOT NULL'];
        $params = [];
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                CAST(r.inviter_user_id AS CHAR) LIKE :search
                OR inv.username LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT r.inviter_user_id,
                       inv.username AS inviter_username,
                       COUNT(*) AS cnt,
                       MIN(r.created_at) AS first_at,
                       MAX(r.created_at) AS last_at
                FROM referrals r
                LEFT JOIN telegram_users inv ON inv.user_id = r.inviter_user_id
                {$whereSql}
                GROUP BY r.inviter_user_id, inv.username
                ORDER BY cnt DESC, r.inviter_user_id DESC";
        if ($length > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        if ($length > 0) {
            $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM (
            SELECT 1
            FROM referrals r
            LEFT JOIN telegram_users inv ON inv.user_id = r.inviter_user_id
            {$whereSql}
            GROUP BY r.inviter_user_id, inv.username
        ) t");
        foreach ($params as $k => $v) {
            $countStmt->bindValue(':' . $k, $v);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(DISTINCT inviter_user_id) FROM referrals')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}
