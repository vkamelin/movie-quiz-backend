<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\HealthService;
use App\Helpers\RedisHelper;
use App\Helpers\View;
use App\Telemetry;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер главной страницы панели управления.
 */
final class HomeController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает статус панели.
     *
     * @param Req $req HTTP-запрос
     * @param Res $res HTTP-ответ
     * @return Res Ответ со статусом
     */
    public function index(Req $req, Res $res): Res
    {
        // 1. COUNT telegram_messages по статусам pending/processing
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM telegram_messages WHERE status IN ('pending','processing') GROUP BY status"
        );
        $pending = 0;
        $processing = 0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['status'] === 'pending') {
                $pending = (int)$row['cnt'];
            }
            if ($row['status'] === 'processing') {
                $processing = (int)$row['cnt'];
            }
        }

        // 2. COUNT telegram_scheduled_messages с send_after <= NOW()
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM telegram_scheduled_messages WHERE send_after <= UTC_TIMESTAMP() AND status = 'pending'"
        );
        $scheduled = (int)$stmt->fetchColumn();

        // 3. SUM/доля success/failed за последний час
        $stmt = $this->db->query(
            'SELECT status, COUNT(*) AS cnt FROM telegram_messages ' .
            "WHERE status IN ('success','failed') AND processed_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) GROUP BY status"
        );
        $success = 0;
        $failed = 0;
        foreach ($stmt->fetchAll() as $row) {
            if ($row['status'] === 'success') {
                $success = (int)$row['cnt'];
            }
            if ($row['status'] === 'failed') {
                $failed = (int)$row['cnt'];
            }
        }
        $totalHour = $success + $failed;
        $successShare = $totalHour > 0 ? round(($success / $totalHour) * 100, 2) : 0.0;
        $failedShare = $totalHour > 0 ? round(($failed / $totalHour) * 100, 2) : 0.0;

        // 4. COUNT DISTINCT telegram_updates.user_id за 24ч
        $stmt = $this->db->query(
            'SELECT COUNT(DISTINCT user_id) FROM telegram_updates WHERE created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)'
        );
        $distinctUsers = (int)$stmt->fetchColumn();

        // 5. COUNT активных telegram_sessions.updated_at за 24ч
        $stmt = $this->db->query(
            'SELECT COUNT(*) FROM telegram_sessions WHERE updated_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)'
        );
        $activeSessions = (int)$stmt->fetchColumn();

        // Последние 10 ошибок
        $stmt = $this->db->query(
            'SELECT id, user_id, error, code, processed_at FROM telegram_messages ' .
            "WHERE status='failed' ORDER BY processed_at DESC LIMIT 10"
        );
        $lastErrors = $stmt->fetchAll();

        // Агрегат success/failed по минутам за 60 минут
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(processed_at, '%Y-%m-%d %H:%i') AS minute, " .
            "SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS success_cnt, " .
            "SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed_cnt " .
            'FROM telegram_messages ' .
            'WHERE processed_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 60 MINUTE) ' .
            'GROUP BY minute ORDER BY minute'
        );
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['minute']] = [
                'success' => (int)$r['success_cnt'],
                'failed' => (int)$r['failed_cnt'],
            ];
        }
        $labels = [];
        $successData = [];
        $failedData = [];
        // Build minute buckets based on UTC to align with DB time window
        $nowTs = time();
        for ($i = 59; $i >= 0; $i--) {
            $ts = $nowTs - ($i * 60);
            $minute = gmdate('Y-m-d H:i', $ts);
            $labels[] = gmdate('H:i', $ts);
            $successData[] = $indexed[$minute]['success'] ?? 0;
            $failedData[] = $indexed[$minute]['failed'] ?? 0;
        }

        // Проверка компонентов приложения
        $health = HealthService::check();

        $queueSizes = null;
        $sendSpeed = null;
        if (Telemetry::enabled()) {
            try {
                $redis = RedisHelper::getInstance();
                $queueSizes = [
                    'p2' => (int)$redis->lLen('telegram:queue:2'),
                    'p1' => (int)$redis->lLen('telegram:queue:1'),
                    'p0' => (int)$redis->lLen('telegram:queue:0'),
                    'dlq' => (int)$redis->lLen('telegram:dlq'),
                ];
            } catch (\RedisException) {
                $queueSizes = null;
            }

            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM telegram_messages WHERE status='success' " .
                'AND processed_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 MINUTE)'
            );
            $sendSpeed = (int)$stmt->fetchColumn();
        }

        $data = [
            'title' => 'Dashboard',
            'pendingMessages' => $pending,
            'processingMessages' => $processing,
            'scheduledMessages' => $scheduled,
            'lastHourSuccess' => $success,
            'lastHourFailed' => $failed,
            'lastHourSuccessShare' => $successShare,
            'lastHourFailedShare' => $failedShare,
            'distinctUsers24h' => $distinctUsers,
            'activeSessions24h' => $activeSessions,
            'lastErrors' => $lastErrors,
            'chartLabels' => $labels,
            'chartSuccess' => $successData,
            'chartFailed' => $failedData,
            'health' => $health,
            'queueSizes' => $queueSizes,
            'sendSpeed' => $sendSpeed,
        ];

        return View::render($res, 'dashboard/index.php', $data, 'layouts/main.php');
    }
}
