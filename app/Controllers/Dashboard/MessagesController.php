<?php

/**
 * Copyright (c) 2025. Vitaliy Kamelin <v.kamelin@gmail.com>
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Flash;
use App\Helpers\MediaBuilder;
use App\Helpers\Path;
use App\Helpers\Push;
use App\Helpers\Response;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Random\RandomException;

/**
 * Контроллер для просмотра и управления отправленными сообщениями.
 */
final class MessagesController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает таблицу сообщений.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Messages',
        ];

        return View::render($res, 'dashboard/messages/index.php', $data, 'layouts/main.php');
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
            $conds[] = 'status = :status';
            $params['status'] = $p['status'];
        }
        if (($p['method'] ?? '') !== '') {
            $conds[] = 'method = :method';
            $params['method'] = $p['method'];
        }
        if (($p['type'] ?? '') !== '') {
            $conds[] = '`type` = :type';
            $params['type'] = $p['type'];
        }
        if (($p['user_id'] ?? '') !== '') {
            $conds[] = 'user_id = :user_id';
            $params['user_id'] = $p['user_id'];
        }
        if (($p['priority'] ?? '') !== '') {
            $conds[] = 'priority = :priority';
            $params['priority'] = (int)$p['priority'];
        }
        if (($p['processed_from'] ?? '') !== '') {
            $conds[] = 'processed_at >= :processed_from';
            $params['processed_from'] = $p['processed_from'];
        }
        if (($p['processed_to'] ?? '') !== '') {
            $conds[] = 'processed_at <= :processed_to';
            $params['processed_to'] = $p['processed_to'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(CAST(id AS CHAR) LIKE :search OR CAST(user_id AS CHAR) LIKE :search OR method LIKE :search OR `type` LIKE :search OR status LIKE :search OR error LIKE :search OR CAST(code AS CHAR) LIKE :search)';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        try {
            $sql = "SELECT id, user_id, method, `type`, status, priority, error, code, processed_at, scheduled_id FROM telegram_messages {$whereSql} ORDER BY id DESC";
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
        } catch (\Throwable $e) {
            // Fallback for legacy schema without scheduled_id
            $sql = "SELECT id, user_id, method, `type`, status, priority, error, code, processed_at FROM telegram_messages {$whereSql} ORDER BY id DESC";
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
            $rows = array_map(static function ($r) {
                $r['scheduled_id'] = null;
                return $r;
            }, $stmt->fetchAll() ?: []);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM telegram_messages {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM telegram_messages')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Создаёт копию указанной записи.
     */
    public function resend(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);

        // Получаем исходные данные сообщения
        $stmt = $this->db->prepare('SELECT user_id, method, `type`, data, priority FROM telegram_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            Flash::add('error', 'Сообщение не найдено');
            return $res->withHeader('Location', '/dashboard/messages')->withStatus(302);
        }

        // Декодируем payload
        $payload = [];
        try {
            $decoded = json_decode((string)$row['data'], true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } catch (\Throwable) {
            // если не удалось декодировать, оставим пустой payload — Push сам поставит chat_id
            $payload = [];
        }

        $chatId = (int)$row['user_id'];
        $method = (string)$row['method'];
        $type = (string)$row['type'];
        $priority = (int)$row['priority'];

        // Гарантируем наличие chat_id в полезной нагрузке
        if (!isset($payload['chat_id']) || empty($payload['chat_id'])) {
            $payload['chat_id'] = $chatId;
        }

        // Переотправляем через унифицированный помощник Push
        $ok = Push::custom($method, $payload, $chatId, $type, $priority);

        if ($ok) {
            Flash::add('success', 'Сообщение повторно поставлено в очередь');
        } else {
            Flash::add('error', 'Не удалось повторно поставить сообщение в очередь');
        }

        return $res->withHeader('Location', '/dashboard/messages')->withStatus(302);
    }

    /**
     * Отдаёт содержимое поля response как файл JSON.
     */
    public function download(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT response FROM telegram_messages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row || $row['response'] === null) {
            return $res->withStatus(404);
        }
        $filename = "response_{$id}.json";
        $res->getBody()->write((string)$row['response']);
        return $res->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Форма для отправки нового сообщения.
     */
    public function create(Req $req, Res $res): Res
    {
        $groups = $this->db->query('SELECT id,name FROM telegram_user_groups ORDER BY name')->fetchAll();
        $params = [
            'title' => 'Send message',
            'groups' => $groups,
            'errors' => [],
            'data' => [],
        ];
        return View::render($res, 'dashboard/messages/create.php', $params, 'layouts/main.php');
    }

    /**
     * Отправляет текстовое сообщение выбранным пользователям.
     */
    public function send(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $type = (string)($p['type'] ?? 'text');
        $mode = (string)($p['mode'] ?? '');

        $data = [
            'type' => $type,
            'text' => trim((string)($p['text'] ?? '')),
            'caption' => trim((string)($p['caption'] ?? '')),
            'parse_mode' => trim((string)($p['parse_mode'] ?? '')),
            'has_spoiler' => !empty($p['has_spoiler']),
            'duration' => trim((string)($p['duration'] ?? '')),
            'performer' => trim((string)($p['performer'] ?? '')),
            'title' => trim((string)($p['title'] ?? '')),
            'width' => trim((string)($p['width'] ?? '')),
            'height' => trim((string)($p['height'] ?? '')),
            'length' => trim((string)($p['length'] ?? '')),
            'mode' => $mode,
            'user' => trim((string)($p['user'] ?? '')),
            'users' => $p['users'] ?? [],
            'group_id' => $p['group_id'] ?? '',
            'msg_type' => trim((string)($p['msg_type'] ?? 'message')),
            'send_mode' => (string)($p['send_mode'] ?? 'now'),
            'send_after' => trim((string)($p['send_after'] ?? '')),
        ];

        $errors = [];
        $sender = null;
        $storedFiles = [];

        $sendAfter = null;
        if ($data['send_mode'] === 'schedule') {
            $sa = $data['send_after'];
            if ($sa === '') {
                $errors[] = 'дата отправки требуется для расписания';
            } elseif (strtotime($sa) === false) {
                $errors[] = 'дата отправки - недопустимая дата и время';
            } elseif (strtotime($sa) <= time()) {
                $errors[] = 'дата отправки должен быть в будущем';
            } else {
                $sendAfter = $sa;
            }
        }

        $msgType = $data['msg_type'] !== '' ? $data['msg_type'] : 'message';

        switch ($type) {
            case 'text':
                $text = $data['text'];
                if ($text === '') {
                    $errors[] = 'требуется текст сообщения';
                }
                $sender = static fn (int $cid) => Push::text($cid, $text, $msgType, 2, [], $sendAfter);
                break;

            case 'photo':
                $path = $this->storeUploadedFile($_FILES['photo'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл изображения';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                if ($data['has_spoiler']) {
                    $options['has_spoiler'] = true;
                }
                $caption = $data['caption'];
                $sender = static fn (int $cid) => Push::photo($cid, $path, $caption, $msgType, 2, $options, $sendAfter);
                break;

            case 'audio':
                $path = $this->storeUploadedFile($_FILES['audio'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл аудио';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                $d = $this->filterInt($data['duration'], 'duration', $errors);
                if ($d !== null) {
                    $options['duration'] = $d;
                }
                if ($data['performer'] !== '') {
                    $options['performer'] = $data['performer'];
                }
                if ($data['title'] !== '') {
                    $options['title'] = $data['title'];
                }
                $caption = $data['caption'];
                $sender = static fn (int $cid) => Push::audio($cid, $path, $caption, $msgType, 2, $options, $sendAfter);
                break;

            case 'video':
                $path = $this->storeUploadedFile($_FILES['video'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл видео';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                $w = $this->filterInt($data['width'], 'width', $errors);
                if ($w !== null) {
                    $options['width'] = $w;
                }
                $h = $this->filterInt($data['height'], 'height', $errors);
                if ($h !== null) {
                    $options['height'] = $h;
                }
                $d = $this->filterInt($data['duration'], 'duration', $errors);
                if ($d !== null) {
                    $options['duration'] = $d;
                }
                if ($data['has_spoiler']) {
                    $options['has_spoiler'] = true;
                }
                $caption = $data['caption'];
                $sender = static fn (int $cid) => Push::video($cid, $path, $caption, $msgType, 2, $options, $sendAfter);
                break;

            case 'document':
                $path = $this->storeUploadedFile($_FILES['document'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл документа';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                $caption = $data['caption'];
                $sender = static fn (int $cid) => Push::document($cid, $path, $caption, $msgType, 2, $options, $sendAfter);
                break;

            case 'sticker':
                $path = $this->storeUploadedFile($_FILES['sticker'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл стикера';
                    break;
                }
                $storedFiles[] = $path;
                $sender = static fn (int $cid) => Push::sticker($cid, $path, $msgType, 2, [], $sendAfter);
                break;

            case 'animation':
                $path = $this->storeUploadedFile($_FILES['animation'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл анимации';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                $w = $this->filterInt($data['width'], 'width', $errors);
                if ($w !== null) {
                    $options['width'] = $w;
                }
                $h = $this->filterInt($data['height'], 'height', $errors);
                if ($h !== null) {
                    $options['height'] = $h;
                }
                $d = $this->filterInt($data['duration'], 'duration', $errors);
                if ($d !== null) {
                    $options['duration'] = $d;
                }
                if ($data['has_spoiler']) {
                    $options['has_spoiler'] = true;
                }
                $caption = $data['caption'];
                $sender = static fn (int $cid) => Push::animation($cid, $path, $caption, $msgType, 2, $options, $sendAfter);
                break;

            case 'voice':
                $path = $this->storeUploadedFile($_FILES['voice'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл голосового сообщения';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                if ($data['caption'] !== '') {
                    $options['caption'] = $data['caption'];
                }
                if ($data['parse_mode'] !== '') {
                    $options['parse_mode'] = $data['parse_mode'];
                }
                $d = $this->filterInt($data['duration'], 'duration', $errors);
                if ($d !== null) {
                    $options['duration'] = $d;
                }
                $sender = static fn (int $cid) => Push::voice($cid, $path, $msgType, 2, $options, $sendAfter);
                break;

            case 'video_note':
                $path = $this->storeUploadedFile($_FILES['video_note'] ?? []);
                if ($path === null) {
                    $errors[] = 'требуется файл видео кружочка';
                    break;
                }
                $storedFiles[] = $path;
                $options = [];
                $len = $this->filterInt($data['length'], 'length', $errors);
                if ($len !== null) {
                    $options['length'] = $len;
                }
                $d = $this->filterInt($data['duration'], 'duration', $errors);
                if ($d !== null) {
                    $options['duration'] = $d;
                }
                $sender = static fn (int $cid) => Push::videoNote($cid, $path, $msgType, 2, $options, $sendAfter);
                break;

            case 'media_group':
                $uploads = $_FILES['media'] ?? null;
                $media = [];
                if (is_array($uploads) && isset($uploads['tmp_name']) && is_array($uploads['tmp_name'])) {
                    $caption = $data['caption'];
                    $parseMode = $data['parse_mode'];
                    foreach ($uploads['tmp_name'] as $idx => $tmp) {
                        if (($uploads['error'][$idx] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || $tmp === '') {
                            continue;
                        }
                        $storedPath = $this->storeUploadedFile([
                            'tmp_name' => $tmp,
                            'error' => $uploads['error'][$idx] ?? UPLOAD_ERR_OK,
                            'name' => $uploads['name'][$idx] ?? '',
                        ]);
                        if ($storedPath === null) {
                            continue;
                        }
                        $storedFiles[] = $storedPath;
                        $opts = [];
                        if ($idx === 0 && $caption !== '') {
                            $opts['caption'] = $caption;
                            if ($parseMode !== '') {
                                $opts['parse_mode'] = $parseMode;
                            }
                        }
                        $media[] = MediaBuilder::buildInputMedia('photo', $storedPath, $opts);
                    }
                }
                if (!$media) {
                    $errors[] = 'требуются медиа файлы';
                    break;
                }
                $sender = static fn (int $cid) => Push::mediaGroup($cid, $media, $msgType, 2, [], $sendAfter);
                break;

            default:
                $errors[] = 'неизвестный тип сообщения';
        }

        if (!in_array($mode, ['all', 'single', 'selected', 'group'], true)) {
            $errors[] = 'режим недействителен';
        }

        $chatIds = [];
        if (!$errors) {
            switch ($mode) {
                case 'all':
                    $chatIds = $this->db->query('SELECT user_id FROM telegram_users')->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    $chatIds = array_map('intval', $chatIds);
                    break;
                case 'single':
                    $q = $data['user'];
                    if ($q === '') {
                        $errors[] = 'требуется пользователь';
                        break;
                    }
                    if (ctype_digit($q)) {
                        $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE user_id = :uid');
                        $stmt->execute(['uid' => (int)$q]);
                    } else {
                        $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE username = :uname');
                        $stmt->execute(['uname' => $q]);
                    }
                    $id = $stmt->fetchColumn();
                    if ($id !== false) {
                        $chatIds[] = (int)$id;
                    } else {
                        $errors[] = 'Пользователь не найден';
                    }
                    break;
                case 'selected':
                    $users = is_array($p['users'] ?? null) ? $p['users'] : [];
                    if (!$users) {
                        $errors[] = 'Не выбрано ни одного пользователя';
                        break;
                    }
                    foreach ($users as $u) {
                        $u = trim((string)$u);
                        if ($u === '') {
                            continue;
                        }
                        if (ctype_digit($u)) {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE user_id = :uid');
                            $stmt->execute(['uid' => (int)$u]);
                        } else {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE username = :uname');
                            $stmt->execute(['uname' => $u]);
                        }
                        $id = $stmt->fetchColumn();
                        if ($id !== false) {
                            $chatIds[] = (int)$id;
                        }
                    }
                    if (!$chatIds) {
                        $errors[] = 'Пользователи не найдены';
                    }
                    break;
                case 'group':
                    $gid = (int)($p['group_id'] ?? 0);
                    if ($gid <= 0) {
                        $errors[] = 'требуется идентификатор group_id';
                        break;
                    }
                    $stmt = $this->db->prepare('SELECT tu.user_id FROM telegram_user_group_user ugu JOIN telegram_users tu ON tu.id = ugu.user_id WHERE ugu.group_id = :gid');
                    $stmt->execute(['gid' => $gid]);
                    $chatIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    $chatIds = array_map('intval', $chatIds);
                    if (!$chatIds) {
                        $errors[] = 'Group is empty';
                    }
                    break;
            }
        }

        // If it's a scheduled batch, create a single record with targeting metadata
        if (!$errors && $data['send_mode'] === 'schedule') {
            $method = null;
            $payload = [];
            $priority = 2;

            // Build method/payload based on message type, excluding chat_id
            switch ($type) {
                case 'text':
                    $method = 'sendMessage';
                    $payload = array_filter([
                        'text' => $data['text'],
                        'parse_mode' => $data['parse_mode'] ?: null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'photo':
                    $method = 'sendPhoto';
                    $payload = array_filter([
                        'photo' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                        'has_spoiler' => $data['has_spoiler'] ? true : null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'audio':
                    $method = 'sendAudio';
                    $payload = array_filter([
                        'audio' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                        'duration' => $this->filterInt($data['duration'], 'duration', $errors) ?? null,
                        'performer' => $data['performer'] ?: null,
                        'title' => $data['title'] ?: null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'video':
                    $method = 'sendVideo';
                    $payload = array_filter([
                        'video' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                        'width' => $this->filterInt($data['width'], 'width', $errors) ?? null,
                        'height' => $this->filterInt($data['height'], 'height', $errors) ?? null,
                        'duration' => $this->filterInt($data['duration'], 'duration', $errors) ?? null,
                        'has_spoiler' => $data['has_spoiler'] ? true : null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'document':
                    $method = 'sendDocument';
                    $payload = array_filter([
                        'document' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'sticker':
                    $method = 'sendSticker';
                    $payload = array_filter([
                        'sticker' => $storedFiles[0] ?? null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'animation':
                    $method = 'sendAnimation';
                    $payload = array_filter([
                        'animation' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                        'width' => $this->filterInt($data['width'], 'width', $errors) ?? null,
                        'height' => $this->filterInt($data['height'], 'height', $errors) ?? null,
                        'duration' => $this->filterInt($data['duration'], 'duration', $errors) ?? null,
                        'has_spoiler' => $data['has_spoiler'] ? true : null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'voice':
                    $method = 'sendVoice';
                    $payload = array_filter([
                        'voice' => $storedFiles[0] ?? null,
                        'caption' => $data['caption'] ?: null,
                        'parse_mode' => $data['parse_mode'] ?: null,
                        'duration' => $this->filterInt($data['duration'], 'duration', $errors) ?? null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'video_note':
                    $method = 'sendVideoNote';
                    $payload = array_filter([
                        'video_note' => $storedFiles[0] ?? null,
                    ], static fn ($v) => $v !== null && $v !== '');
                    break;
                case 'media_group':
                    $method = 'sendMediaGroup';
                    $payload = [];
                    $media = [];
                    foreach (($_FILES['media'] ?? []) as $idx => $f) {
                        $storedPath = $this->storeUploadedFile($f);
                        if ($storedPath !== null) {
                            $opts = [];
                            if (($p['parse_mode'][$idx] ?? '') !== '') {
                                $opts['parse_mode'] = (string)$p['parse_mode'][$idx];
                            }
                            if (!empty($p['has_spoiler'][$idx])) {
                                $opts['has_spoiler'] = true;
                            }
                            $caption = trim((string)($p['caption'][$idx] ?? ''));
                            $media[] = \App\Helpers\MediaBuilder::buildInputMedia('photo', $storedPath, $caption, $opts);
                        }
                    }
                    if (!$media) {
                        $errors[] = 'требуются медиа файлы';
                    }
                    $payload['media'] = $media;
                    break;
                default:
                    $errors[] = 'неизвестный тип сообщения';
            }

            if (!$errors && $method !== null) {
                $target = ['type' => $mode];
                if ($mode === 'group') {
                    $gid = (int)($p['group_id'] ?? 0);
                    if ($gid <= 0) {
                        $errors[] = 'требуется идентификатор group_id';
                    }
                    $target['group_id'] = $gid;
                } elseif ($mode === 'selected') {
                    $users = is_array($p['users'] ?? null) ? $p['users'] : [];
                    $userIds = [];
                    foreach ($users as $u) {
                        $u = trim((string)$u);
                        if ($u === '') {
                            continue;
                        }
                        if (ctype_digit($u)) {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE user_id = :uid');
                            $stmt->execute(['uid' => (int)$u]);
                        } else {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE username = :uname');
                            $stmt->execute(['uname' => $u]);
                        }
                        $id = $stmt->fetchColumn();
                        if ($id !== false) {
                            $userIds[] = (int)$id;
                        }
                    }
                    if (!$userIds) {
                        $errors[] = 'Пользователи не найдены';
                    }
                    $target['user_ids'] = $userIds;
                } elseif ($mode === 'single') {
                    // Convert single to selected with single recipient
                    $q = trim((string)($p['user'] ?? ''));
                    if ($q === '') {
                        $errors[] = 'требуется пользователь';
                    } else {
                        if (ctype_digit($q)) {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE user_id = :uid');
                            $stmt->execute(['uid' => (int)$q]);
                        } else {
                            $stmt = $this->db->prepare('SELECT user_id FROM telegram_users WHERE username = :uname');
                            $stmt->execute(['uname' => $q]);
                        }
                        $id = $stmt->fetchColumn();
                        if ($id === false) {
                            $errors[] = 'Пользователь не найден';
                        } else {
                            $target['type'] = 'selected';
                            $target['user_ids'] = [(int)$id];
                        }
                    }
                }

                if (!$errors) {
                    // Allow user-provided analytics type (optional). If not provided, fall back to msgType
                    $userType = trim((string)($p['custom_type'] ?? ($p['analytics_type'] ?? '')));
                    $statsType = $userType !== '' ? $userType : $msgType;
                    $ok = \App\Helpers\Scheduled::createBatch(
                        $method,
                        $payload,
                        $statsType,
                        $priority,
                        (string)$sendAfter,
                        $target
                    );
                    if ($ok) {
                        Flash::add('success', 'Рассылка запланирована');
                        return $res->withHeader('Location', '/dashboard/messages')->withStatus(302);
                    }
                    $errors[] = 'Не удалось создать отложенную рассылку';
                }
            }
        }

        // Immediate (non-scheduled) send path stays as before
        if (!$errors && $data['send_mode'] !== 'schedule' && $chatIds && $sender !== null) {
            $ok = true;
            foreach ($chatIds as $cid) {
                $ok = $sender($cid) && $ok;
            }
            if ($ok) {
                Flash::add('success', 'Сообщение поставлено в очередь');
                return $res->withHeader('Location', '/dashboard/messages')->withStatus(302);
            }
            $errors[] = 'Не удалось поместить сообщение в очередь';
        } elseif (!$errors && $data['send_mode'] !== 'schedule' && $chatIds && $sender === null) {
            // Recipients selected but sender not created (e.g., missing message content)
            $errors[] = 'Не удалось подготовить сообщение: проверьте заполнение полей';
        } elseif (!$errors && $data['send_mode'] !== 'schedule' && !$chatIds) {
            // No recipients selected - this is an error condition
            $errors[] = 'Не выбрано ни одного получателя';
        }

        if ($errors) {
            foreach ($storedFiles as $f) {
                @unlink($f);
            }
            foreach ($errors as $e) {
                Flash::add('error', $e);
            }
        }

        $groups = $this->db->query('SELECT id,name FROM telegram_user_groups ORDER BY name')->fetchAll();
        $params = [
            'title' => 'Отправить сообщение',
            'groups' => $groups,
            'errors' => $errors,
            'data' => $data,
        ];
        return View::render($res, 'dashboard/messages/create.php', $params, 'layouts/main.php');
    }

    /**
     * Сохраняет загруженный файл в постоянном хранилище.
     *
     * @param array $file Массив данных файла, включающий 'tmp_name' и 'error'.
     *
     * @return string|null Путь к сохраненному файлу или null, если загрузка не удалась.
     * @throws RandomException
     */
    private function storeUploadedFile(array $file): ?string
    {
        $tmp = $file['tmp_name'] ?? '';
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($tmp === '' || $err !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        $dir = Path::base('storage/messages');
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return null;
        }

        $name = bin2hex(random_bytes(16));
        if ($ext !== '') {
            $name .= '.' . $ext;
        }
        $dest = $dir . '/' . $name;

        // move_uploaded_file works only for HTTP uploads; fall back to rename for tests
        if (!@move_uploaded_file($tmp, $dest) && !@rename($tmp, $dest)) {
            return null;
        }

        return $dest;
    }

    /**
     * Валидирует целое поле с возможностью диапазона.
     */
    private function filterInt(string $value, string $field, array &$errors, int $min = 0, int $max = PHP_INT_MAX): ?int
    {
        if ($value === '') {
            return null;
        }
        if (!ctype_digit($value)) {
            $errors[] = $field . ' must be an integer';
            return null;
        }
        $int = (int)$value;
        if ($int < $min || $int > $max) {
            $errors[] = $field . ' must be between ' . $min . ' and ' . $max;
            return null;
        }
        return $int;
    }
}
