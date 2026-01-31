<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Response;
use App\Helpers\View;
use App\Helpers\Push;
use App\Helpers\Flash;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер для просмотра обновлений Telegram.
 */
final class UpdatesController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу обновлений.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Обновления',
        ];

        return View::render($res, 'dashboard/updates/index.php', $data, 'layouts/main.php');
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

        if (($p['type'] ?? '') !== '') {
            $conds[] = '`type` = :type';
            $params['type'] = $p['type'];
        }
        if (($p['user_id'] ?? '') !== '') {
            $conds[] = 'user_id = :user_id';
            $params['user_id'] = $p['user_id'];
        }
        if (($p['message_id'] ?? '') !== '') {
            $conds[] = 'message_id = :message_id';
            $params['message_id'] = $p['message_id'];
        }
        if (($p['created_from'] ?? '') !== '') {
            $conds[] = 'created_at >= :created_from';
            $params['created_from'] = $p['created_from'];
        }
        if (($p['created_to'] ?? '') !== '') {
            $conds[] = 'created_at <= :created_to';
            $params['created_to'] = $p['created_to'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(CAST(id AS CHAR) LIKE :search OR CAST(update_id AS CHAR) LIKE :search OR CAST(user_id AS CHAR) LIKE :search OR CAST(message_id AS CHAR) LIKE :search OR `type` LIKE :search)';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT id, update_id, user_id, message_id, `type`, sent_at, created_at FROM telegram_updates {$whereSql} ORDER BY id DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM telegram_updates {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM telegram_updates')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Полноценный просмотр обновления: форматированный JSON и разбор по типам.
     */
    public function show(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT id, update_id, user_id, message_id, `type`, sent_at, created_at, data FROM telegram_updates WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return $res->withStatus(404);
        }

        $raw = (string)($row['data'] ?? '');
        $decoded = null;
        $pretty = $raw;
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable) {
            // keep raw
        }

        $decodedArr = is_array($decoded) ? $decoded : [];
        $summary = $this->buildSummary($decodedArr);

        // Target info for replies (only for message updates)
        $replyTarget = null;
        if (isset($decodedArr['message']) && is_array($decodedArr['message'])) {
            $m = $decodedArr['message'];
            $chatId = $m['chat']['id'] ?? null;
            $msgId = $m['message_id'] ?? null;
            if ($chatId !== null && $msgId !== null) {
                $replyTarget = [
                    'chat_id' => (int)$chatId,
                    'message_id' => (int)$msgId,
                ];
            }
        }

        $submenu = [
            ['url' => '/dashboard/updates', 'title' => 'Входящие', 'icon' => 'bi bi-telegram'],
            ['url' => '/dashboard/updates/' . $id, 'title' => 'Просмотр', 'class' => 'active', 'icon' => 'bi bi-eye'],
        ];

        $data = [
            'title' => 'Обновление #' . $id,
            'row' => $row,
            'rawPretty' => $pretty,
            'json' => $decoded,
            'summary' => $summary,
            'submenu' => $submenu,
            'replyTarget' => $replyTarget,
        ];

        return View::render($res, 'dashboard/updates/view.php', $data, 'layouts/main.php');
    }

    /**
     * Отправка ответа на сообщение (только для updates с message)
     */
    public function reply(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $p = (array)$req->getParsedBody();
        $text = trim((string)($p['text'] ?? ''));
        if ($text === '') {
            Flash::add('error', 'Текст ответа обязателен');
            return $res->withHeader('Location', '/dashboard/updates/' . $id)->withStatus(302);
        }

        // Load update and extract message target
        $stmt = $this->db->prepare('SELECT data FROM telegram_updates WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return $res->withStatus(404);
        }
        $decoded = null;
        try {
            $decoded = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $decoded = null;
        }

        if (!is_array($decoded) || !isset($decoded['message']) || !is_array($decoded['message'])) {
            Flash::add('error', 'Нельзя ответить: это не входящее сообщение');
            return $res->withHeader('Location', '/dashboard/updates/' . $id)->withStatus(302);
        }

        $m = $decoded['message'];
        $chatId = isset($m['chat']['id']) ? (int)$m['chat']['id'] : 0;
        $msgId = isset($m['message_id']) ? (int)$m['message_id'] : 0;
        if ($chatId === 0 || $msgId === 0) {
            Flash::add('error', 'Не удалось определить цель для ответа');
            return $res->withHeader('Location', '/dashboard/updates/' . $id)->withStatus(302);
        }

        $ok = Push::text($chatId, $text, 'reply', 2, ['reply_to_message_id' => $msgId]);
        if ($ok) {
            Flash::add('success', 'Ответ отправлен');
        } else {
            Flash::add('error', 'Не удалось поставить ответ в очередь');
        }
        return $res->withHeader('Location', '/dashboard/updates/' . $id)->withStatus(302);
    }

    /**
     * Строит краткое описание обновления с учётом разных типов объектов.
     *
     * @param array<string,mixed> $u
     * @return array<string,mixed>
     */
    private function buildSummary(array $u): array
    {
        if ($u === []) {
            return ['type' => 'unknown', 'info' => []];
        }

        $get = static function (array $arr, string $path, mixed $default = null): mixed {
            $parts = explode('.', $path);
            $cur = $arr;
            foreach ($parts as $p) {
                if (!is_array($cur) || !array_key_exists($p, $cur)) {
                    return $default;
                }
                $cur = $cur[$p];
            }
            return $cur;
        };

        $fmtUser = static function ($user): string {
            if (!is_array($user)) { return ''; }
            $id = (string)($user['id'] ?? '');
            $name = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
            $uname = (string)($user['username'] ?? '');
            $out = $name !== '' ? $name : ($uname !== '' ? '@' . $uname : 'User');
            if ($id !== '') { $out .= ' (#' . $id . ')'; }
            return $out;
        };

        $fmtChat = static function ($chat): string {
            if (!is_array($chat)) { return ''; }
            $id = (string)($chat['id'] ?? '');
            $title = (string)($chat['title'] ?? '');
            $uname = (string)($chat['username'] ?? '');
            $type = (string)($chat['type'] ?? '');
            $label = $title !== '' ? $title : ($uname !== '' ? '@' . $uname : 'chat');
            $suffix = $type !== '' ? " ({$type})" : '';
            if ($id !== '') { $label .= ' (#' . $id . ')' . $suffix; }
            return $label;
        };

        $info = [];
        $type = 'unknown';

        // Determine primary update type
        $keys = [
            'message','edited_message','channel_post','edited_channel_post','callback_query',
            'inline_query','chosen_inline_result','shipping_query','pre_checkout_query',
            'my_chat_member','chat_member','chat_join_request','poll','poll_answer',
            'message_reaction','message_reaction_count'
        ];
        $root = null;
        foreach ($keys as $k) {
            if (array_key_exists($k, $u)) {
                $type = $k;
                $root = is_array($u[$k] ?? null) ? $u[$k] : null;
                break;
            }
        }

        // Fallback to raw keys
        if ($root === null) {
            $type = array_key_first($u) ?? 'unknown';
            $root = is_array($u[$type] ?? null) ? $u[$type] : $u;
        }

        // Build info by type
        switch ($type) {
            case 'message':
            case 'edited_message':
            case 'channel_post':
            case 'edited_channel_post':
                $isEdited = str_starts_with($type, 'edited_');
                $info['Событие'] = $isEdited ? 'Изменение сообщения' : 'Сообщение';
                $info['От'] = $fmtUser($root['from'] ?? []);
                $info['Чат'] = $fmtChat($root['chat'] ?? []);
                $info['ID сообщения'] = $root['message_id'] ?? null;
                $info['Дата'] = isset($root['date']) ? date('Y-m-d H:i:s', (int)$root['date']) : null;
                if (isset($root['text'])) { $info['Текст'] = $root['text']; }
                if (isset($root['caption'])) { $info['Подпись'] = $root['caption']; }
                $mediaTypes = ['photo','video','audio','document','animation','sticker','voice','video_note'];
                foreach ($mediaTypes as $mt) {
                    if (isset($root[$mt])) {
                        $info['Медиа'] = $mt;
                        break;
                    }
                }
                if (isset($root['entities']) && is_array($root['entities'])) {
                    $info['Энтити'] = count($root['entities']);
                }
                break;
            case 'callback_query':
                $info['Событие'] = 'CallbackQuery';
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Данные'] = $root['data'] ?? null;
                if (isset($root['message'])) {
                    $info['Сообщение.ID'] = $get($root, 'message.message_id');
                    $info['Сообщение.Текст'] = $get($root, 'message.text');
                    $info['Сообщение.Чат'] = $fmtChat($get($root, 'message.chat', []));
                }
                break;
            case 'inline_query':
                $info['Событие'] = 'InlineQuery';
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Запрос'] = $root['query'] ?? '';
                break;
            case 'chosen_inline_result':
                $info['Событие'] = 'ChosenInlineResult';
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Результат'] = $root['result_id'] ?? '';
                $info['Запрос'] = $root['query'] ?? '';
                break;
            case 'shipping_query':
                $info['Событие'] = 'ShippingQuery';
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Адрес'] = isset($root['shipping_address']) ? json_encode($root['shipping_address'], JSON_UNESCAPED_UNICODE) : null;
                break;
            case 'pre_checkout_query':
                $info['Событие'] = 'PreCheckoutQuery';
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Валюта'] = $root['currency'] ?? null;
                $info['Сумма'] = isset($root['total_amount']) ? ((int)$root['total_amount'])/100 : null;
                break;
            case 'my_chat_member':
            case 'chat_member':
                $info['Событие'] = $type === 'my_chat_member' ? 'Статус бота изменён' : 'Статус пользователя изменён';
                $info['Чат'] = $fmtChat($root['chat'] ?? []);
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Был'] = $get($root, 'old_chat_member.status');
                $info['Стал'] = $get($root, 'new_chat_member.status');
                break;
            case 'chat_join_request':
                $info['Событие'] = 'Запрос на вступление';
                $info['Чат'] = $fmtChat($root['chat'] ?? []);
                $info['Пользователь'] = $fmtUser($root['from'] ?? []);
                $info['Био'] = $root['bio'] ?? null;
                break;
            case 'poll':
                $info['Событие'] = 'Опрос';
                $info['Вопрос'] = $root['question'] ?? null;
                $info['Опции'] = isset($root['options']) && is_array($root['options']) ? array_map(static fn($o) => $o['text'] ?? '', $root['options']) : [];
                break;
            case 'poll_answer':
                $info['Событие'] = 'Ответ на опрос';
                $info['Пользователь'] = $fmtUser($root['user'] ?? []);
                $info['Poll ID'] = $root['poll_id'] ?? null;
                $info['Ответы (индексы)'] = isset($root['option_ids']) ? implode(',', (array)$root['option_ids']) : '';
                break;
            case 'message_reaction':
                $info['Событие'] = 'Реакция на сообщение';
                $info['Чат'] = $fmtChat($root['chat'] ?? []);
                $info['Сообщение'] = $root['message_id'] ?? null;
                $info['От'] = $fmtUser($root['user'] ?? []);
                $info['Реакции'] = isset($root['new_reaction']) ? json_encode($root['new_reaction'], JSON_UNESCAPED_UNICODE) : null;
                break;
            case 'message_reaction_count':
                $info['Событие'] = 'Счётчик реакций';
                $info['Чат'] = $fmtChat($root['chat'] ?? []);
                $info['Сообщение'] = $root['message_id'] ?? null;
                $info['Суммарно'] = isset($root['reactions']) ? json_encode($root['reactions'], JSON_UNESCAPED_UNICODE) : null;
                break;
            default:
                $info['Событие'] = $type;
                $info['Данные'] = json_encode($root, JSON_UNESCAPED_UNICODE);
        }

        // Clean nulls
        foreach ($info as $k => $v) {
            if ($v === null || $v === '') {
                unset($info[$k]);
            }
        }

        return [
            'type' => $type,
            'info' => $info,
        ];
    }
}
