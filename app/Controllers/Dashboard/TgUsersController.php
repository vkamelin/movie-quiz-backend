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
 * Контроллер для просмотра и управления пользователями Telegram.
 */
final class TgUsersController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу пользователей.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Telegram Users',
        ];

        return View::render($res, 'dashboard/tg-users/index.php', $data, 'layouts/main.php');
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

        foreach (['is_premium', 'is_user_banned', 'is_bot_banned', 'is_subscribed'] as $flag) {
            if (($p[$flag] ?? '') !== '') {
                $conds[] = "$flag = :$flag";
                $params[$flag] = (int)$p[$flag];
            }
        }
        if (($p['language_code'] ?? '') !== '') {
            $conds[] = 'language_code = :language_code';
            $params['language_code'] = $p['language_code'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(
                CAST(user_id AS CHAR) LIKE :search OR
                username LIKE :search OR
                utm LIKE :search OR
                referral_code LIKE :search
            )';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT id, user_id, username, first_name, last_name, language_code, is_premium, is_user_banned, is_bot_banned, is_subscribed, utm, referral_code FROM telegram_users {$whereSql} ORDER BY id DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM telegram_users {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM telegram_users')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Ищет пользователей по переданным полям.
     *
     * @param Req $req HTTP-запрос
     * @param Res $res HTTP-ответ
     * @return Res JSON с найденными пользователями
     */
    public function search(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $conds = [];
        $params = [];

        // Consolidated free-text search across fields
        $q = trim((string)($p['q'] ?? ($p['username'] ?? ($p['first_name'] ?? ($p['last_name'] ?? ($p['user_id'] ?? ''))))));
        if ($q !== '') {
            // When PDO emulation is disabled, repeated named placeholders cause HY093.
            // Use unique placeholders for each occurrence.
            $conds[] = '(
                CAST(user_id AS CHAR) LIKE :q1 OR
                username LIKE :q2 OR
                first_name LIKE :q3 OR
                last_name LIKE :q4
            )';
            $qv = '%' . $q . '%';
            $params['q1'] = $qv;
            $params['q2'] = $qv;
            $params['q3'] = $qv;
            $params['q4'] = $qv;
        }
        if (($p['is_premium'] ?? '') !== '') {
            $conds[] = 'is_premium = :is_premium';
            $params['is_premium'] = (int)$p['is_premium'];
        }

        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        // Ограничиваем количество результатов для интерфейса добавления в группу
        $limit = min(50, max(1, (int)($p['limit'] ?? 10)));

        $sql = "SELECT id, user_id, username, first_name, last_name, is_premium FROM telegram_users {$whereSql} ORDER BY id DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return Response::json($res, 200, $rows);
    }

    /**
     * Отображает карточку пользователя с последними сообщениями и обновлениями.
     */
    public function view(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT * FROM telegram_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return $res->withStatus(404);
        }

        $msgStmt = $this->db->prepare('SELECT id, method, `type`, status, processed_at FROM telegram_messages WHERE user_id = :uid ORDER BY id DESC LIMIT 10');
        $msgStmt->execute(['uid' => $user['user_id']]);
        $messages = $msgStmt->fetchAll();

        $updStmt = $this->db->prepare('SELECT id, update_id, `type`, created_at FROM telegram_updates WHERE user_id = :uid ORDER BY id DESC LIMIT 10');
        $updStmt->execute(['uid' => $user['user_id']]);
        $updates = $updStmt->fetchAll();

        // Последние приглашенные рефералы
        $refStmt = $this->db->prepare('SELECT r.invitee_user_id, r.created_at, tu.username, tu.first_name, tu.last_name
            FROM referrals r
            LEFT JOIN telegram_users tu ON tu.user_id = r.invitee_user_id
            WHERE r.inviter_user_id = :uid
            ORDER BY r.id DESC
            LIMIT 50');
        $refStmt->execute(['uid' => $user['user_id']]);
        $referrals = $refStmt->fetchAll();

        $data = [
            'title' => 'User ' . ($user['username'] ?: $user['user_id']),
            'user' => $user,
            'messages' => $messages,
            'updates' => $updates,
            'referrals' => $referrals,
        ];

        return View::render($res, 'dashboard/tg-users/view.php', $data, 'layouts/main.php');
    }

    /**
     * Полная история переписки с пользователем в виде чата (только просмотр).
     */
    public function chat(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT * FROM telegram_users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return $res->withStatus(404);
        }

        $uid = (int)$user['user_id'];

        // Load incoming messages (updates)
        $uStmt = $this->db->prepare(
            "SELECT `type`, `data`, `sent_at`, `created_at` FROM telegram_updates WHERE user_id = :uid AND `type` IN ('message') ORDER BY id ASC"
        );
        $uStmt->execute(['uid' => $uid]);
        $updates = $uStmt->fetchAll() ?: [];

        // Load outgoing messages
        $mStmt = $this->db->prepare(
            "SELECT `method`, `type`, `data`, `response`, `status`, `processed_at`, `sent_at`, `created_at` FROM telegram_messages WHERE user_id = :uid ORDER BY id ASC"
        );
        $mStmt->execute(['uid' => $uid]);
        $messages = $mStmt->fetchAll() ?: [];

        // Build chat items
        $fileUrlCache = [];
        $items = [];

        foreach ($updates as $row) {
            $it = $this->buildItemFromUpdateRow($row, $fileUrlCache);
            if ($it !== null) {
                $items[] = $it;
            }
        }
        foreach ($messages as $row) {
            foreach ($this->buildItemsFromMessageRow($row, $fileUrlCache) as $it) {
                $items[] = $it;
            }
        }

        // Sort by timestamp
        usort($items, static function (array $a, array $b): int {
            return ($a['ts'] ?? 0) <=> ($b['ts'] ?? 0);
        });

        $data = [
            'title' => 'Чат ' . ($user['username'] ?: $user['user_id']),
            'user' => $user,
            'items' => $items,
        ];

        return View::render($res, 'dashboard/tg-users/chat.php', $data, 'layouts/main.php');
    }

    /**
     * Build chat item from incoming update row.
     * @param array $row
     * @param array<string,string> $cache
     * @return array<string,mixed>|null
     */
    private function buildItemFromUpdateRow(array $row, array &$cache): ?array
    {
        $data = [];
        try {
            $data = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable) {
            $data = [];
        }
        $msg = $data['message'] ?? null;
        if (!is_array($msg)) {
            return null;
        }

        $base = [
            'direction' => 'in',
            'ts' => (int)($msg['date'] ?? strtotime((string)($row['sent_at'] ?? $row['created_at'] ?? 'now'))),
        ];

        // text
        if (isset($msg['text'])) {
            return $base + ['type' => 'text', 'text' => (string)$msg['text']];
        }

        // caption
        $caption = isset($msg['caption']) ? (string)$msg['caption'] : null;

        // photo
        if (!empty($msg['photo']) && is_array($msg['photo'])) {
            $photo = end($msg['photo']);
            $fid = is_array($photo) ? ($photo['file_id'] ?? null) : null;
            return $base + [
                'type' => 'photo',
                'file_url' => $fid ? $this->getFileUrl($fid, $cache) : null,
                'caption' => $caption,
            ];
        }

        // video / animation / video_note / sticker / document / audio / voice
        foreach (['video', 'animation', 'video_note', 'sticker', 'document', 'audio', 'voice'] as $k) {
            if (isset($msg[$k]) && is_array($msg[$k])) {
                $fid = $msg[$k]['file_id'] ?? null;
                $fileName = $msg[$k]['file_name'] ?? null;
                return $base + [
                    'type' => $k,
                    'file_url' => $fid ? $this->getFileUrl($fid, $cache) : null,
                    'file_name' => $fileName,
                    'caption' => $caption,
                ];
            }
        }

        return $base + ['type' => (string)($row['type'] ?? 'unknown')];
    }

    /**
     * Build one or more chat items from outgoing message row.
     * @param array $row
     * @param array<string,string> $cache
     * @return array<int, array<string,mixed>>
     */
    private function buildItemsFromMessageRow(array $row, array &$cache): array
    {
        $items = [];
        $type = (string)($row['type'] ?? '');
        $ts = strtotime((string)($row['processed_at'] ?? $row['sent_at'] ?? $row['created_at'] ?? 'now'));

        // text
        if ($type === 'message' || ($row['method'] ?? '') === 'sendMessage') {
            $data = [];
            try {
                $data = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR) ?: [];
            } catch (\Throwable) {
                $data = [];
            }
            $text = (string)($data['text'] ?? '');
            if ($text !== '') {
                $items[] = [
                    'direction' => 'out',
                    'type' => 'text',
                    'text' => $text,
                    'ts' => $ts,
                ];
            }
            return $items;
        }

        // For media and others try to resolve file_id from response
        $resp = [];
        try {
            $resp = json_decode((string)$row['response'], true, 512, JSON_THROW_ON_ERROR) ?: [];
        } catch (\Throwable) {
            $resp = [];
        }

        $result = $resp['result'] ?? null;

        // sendMediaGroup returns array of messages
        if (is_array($result) && array_is_list($result)) {
            foreach ($result as $m) {
                if (!is_array($m)) { continue; }
                $items[] = $this->buildOutMediaItemFromMessage($m, $ts, $cache);
            }
            return array_values(array_filter($items));
        }

        if (is_array($result)) {
            $item = $this->buildOutMediaItemFromMessage($result, $ts, $cache);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Build single outgoing media item from Telegram API message payload
     * extracted from send response.
     * @param array $msg
     * @param int $ts
     * @param array<string,string> $cache
     * @return array<string,mixed>|null
     */
    private function buildOutMediaItemFromMessage(array $msg, int $ts, array &$cache): ?array
    {
        $base = [
            'direction' => 'out',
            'ts' => (int)($msg['date'] ?? $ts),
        ];
        $caption = isset($msg['caption']) ? (string)$msg['caption'] : null;

        if (!empty($msg['photo']) && is_array($msg['photo'])) {
            $photo = end($msg['photo']);
            $fid = is_array($photo) ? ($photo['file_id'] ?? null) : null;
            return $base + [
                'type' => 'photo',
                'file_url' => $fid ? $this->getFileUrl($fid, $cache) : null,
                'caption' => $caption,
            ];
        }
        foreach (['video', 'animation', 'video_note', 'sticker', 'document', 'audio', 'voice'] as $k) {
            if (isset($msg[$k]) && is_array($msg[$k])) {
                $fid = $msg[$k]['file_id'] ?? null;
                $fileName = $msg[$k]['file_name'] ?? null;
                return $base + [
                    'type' => $k,
                    'file_url' => $fid ? $this->getFileUrl($fid, $cache) : null,
                    'file_name' => $fileName,
                    'caption' => $caption,
                ];
            }
        }
        return null;
    }

    /**
     * Resolve file URL via Telegram getFile. Uses in-memory cache for page build.
     * @param string $fileId
     * @param array<string,string> $cache
     * @return string|null
     */
    private function getFileUrl(string $fileId, array &$cache): ?string
    {
        if (isset($cache[$fileId])) {
            return $cache[$fileId];
        }
        $resp = Request::getFile(['file_id' => $fileId]);
        $ok = method_exists($resp, 'isOk') ? $resp->isOk() : ($resp->ok ?? false);
        $result = $ok ? (method_exists($resp, 'getResult') ? $resp->getResult() : ($resp->result ?? null)) : null;
        $filePath = '';
        if (is_object($result)) {
            $filePath = method_exists($result, 'getFilePath') ? $result->getFilePath() : ($result->file_path ?? '');
        } elseif (is_array($result)) {
            $filePath = (string)($result['file_path'] ?? '');
        }
        if (!$ok || $filePath === '') {
            return null;
        }
        $token = $_ENV['BOT_TOKEN'] ?? '';
        $url = 'https://api.telegram.org/file/bot' . $token . '/' . $filePath;
        $cache[$fileId] = $url;
        return $url;
    }
}
