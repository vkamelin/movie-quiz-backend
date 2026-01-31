<?php

/**
 * Controller for managing uploaded files.
 */

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\FileService;
use App\Helpers\Flash;
use App\Helpers\Response;
use App\Helpers\View;
use Longman\TelegramBot\Request;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

final class FilesController
{
    public function __construct(private PDO $db, private FileService $files)
    {
    }

    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Files',
        ];
        return View::render($res, 'dashboard/files/index.php', $data, 'layouts/main.php');
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
        if (($p['type'] ?? '') !== '') {
            $conds[] = 'type = :type';
            $params['type'] = $p['type'];
        }
        $searchValue = $p['search']['value'] ?? '';
        if ($searchValue !== '') {
            $conds[] = '(original_name LIKE :search OR file_id LIKE :search)';
            $params['search'] = '%' . $searchValue . '%';
        }
        $whereSql = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT id, type, original_name, mime_type, size, file_id, created_at FROM telegram_files {$whereSql} ORDER BY id DESC";
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

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM telegram_files {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM telegram_files')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function create(Req $req, Res $res): Res
    {
        $params = [
            'title' => 'Upload file',
            'errors' => [],
            'data' => [],
        ];
        return View::render($res, 'dashboard/files/create.php', $params, 'layouts/main.php');
    }

    public function store(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $type = (string)($data['type'] ?? '');
        $uploaded = $req->getUploadedFiles();
        $file = $uploaded['file'] ?? null;

        $errors = [];
        if (!in_array($type, ['photo', 'document', 'audio', 'video', 'voice'], true)) {
            $errors[] = 'Unknown type';
        }
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            $errors[] = 'File is required';
        }

        if (!$errors) {
            $tmp = tempnam(sys_get_temp_dir(), 'tgf');
            $file->moveTo($tmp);
            $fileId = null;
            switch ($type) {
                case 'photo':
                    $fileId = $this->files->sendPhoto($tmp);
                    break;
                case 'document':
                    $fileId = $this->files->sendDocument($tmp);
                    break;
                case 'audio':
                    $fileId = $this->files->sendAudio($tmp);
                    break;
                case 'video':
                    $fileId = $this->files->sendVideo($tmp);
                    break;
                case 'voice':
                    $fileId = $this->files->sendVoice($tmp);
                    break;
            }
            @unlink($tmp);
            if ($fileId !== null) {
                Flash::add('success', 'File uploaded');
                return $res->withHeader('Location', '/dashboard/files')->withStatus(302);
            }
            $errors[] = 'Failed to upload';
        }

        $params = [
            'title' => 'Upload file',
            'errors' => $errors,
            'data' => ['type' => $type],
        ];
        return View::render($res, 'dashboard/files/create.php', $params, 'layouts/main.php');
    }

    public function show(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT type, file_id FROM telegram_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return $res->withStatus(404);
        }

        $response = Request::getFile(['file_id' => $row['file_id']]);
        $ok = method_exists($response, 'isOk') ? $response->isOk() : ($response->ok ?? false);
        $result = $ok ? (method_exists($response, 'getResult') ? $response->getResult() : ($response->result ?? null)) : null;
        $filePath = '';
        if (is_object($result)) {
            $filePath = method_exists($result, 'getFilePath') ? $result->getFilePath() : ($result->file_path ?? '');
        } elseif (is_array($result)) {
            $filePath = $result['file_path'] ?? '';
        }
        if (!$ok || $filePath === '') {
            return $res->withStatus(500);
        }

        $token = $_ENV['BOT_TOKEN'] ?? '';
        $url = 'https://api.telegram.org/file/bot' . $token . '/' . $filePath;
        $html = '';
        switch ($row['type']) {
            case 'photo':
                $html = '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" class="img-fluid">';
                break;
            case 'audio':
            case 'voice':
                $html = '<audio controls src="' . htmlspecialchars($url, ENT_QUOTES) . '"></audio>';
                break;
            case 'video':
                $html = '<video controls src="' . htmlspecialchars($url, ENT_QUOTES) . '" class="img-fluid"></video>';
                break;
            default:
                $html = '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">Download</a>';
        }
        $res->getBody()->write($html);
        return $res->withHeader('Content-Type', 'text/html');
    }
}
