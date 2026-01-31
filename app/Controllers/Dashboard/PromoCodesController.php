<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Flash;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Psr\Http\Message\UploadedFileInterface;

final class PromoCodesController
{
    public function __construct(private PDO $db)
    {
    }

    public function index(Req $req, Res $res): Res
    {
        $p = $req->getQueryParams();
        $status = isset($p['status']) && $p['status'] !== '' ? (string)$p['status'] : null;
        $batchId = isset($p['batch_id']) && $p['batch_id'] !== '' ? (int)$p['batch_id'] : null;
        $q = isset($p['q']) ? trim((string)$p['q']) : '';
        $limit = min(50, max(20, (int)($p['limit'] ?? 20)));
        $page = max(1, (int)($p['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $conds = [];
        $args = [];
        if ($status !== null) {
            $conds[] = 'status = ?';
            $args[] = $status;
        }
        if ($batchId !== null) {
            $conds[] = 'batch_id = ?';
            $args[] = $batchId;
        }
        if ($q !== '') {
            $conds[] = 'code LIKE ?';
            $args[] = '%' . $q . '%';
        }
        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT id, batch_id, code, status, expires_at, issued_at FROM promo_codes {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($args as $i => $val) {
            $stmt->bindValue($i + 1, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $cnt = $this->db->prepare("SELECT COUNT(*) FROM promo_codes {$where}");
        foreach ($args as $i => $val) {
            $cnt->bindValue($i + 1, $val);
        }
        $cnt->execute();
        $total = (int)$cnt->fetchColumn();

        $batches = $this->db->query('SELECT id, filename, created_at FROM promo_code_batches ORDER BY id DESC LIMIT 200')->fetchAll();

        return View::render($res, 'dashboard/promo_codes/index.php', [
            'title' => 'Промокоды',
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'batch_id' => $batchId,
            'q' => $q,
            'batches' => $batches,
        ], 'layouts/main.php');
    }

    public function upload(Req $req, Res $res): Res
    {
        return View::render($res, 'dashboard/promo_codes/upload.php', [
            'title' => 'Загрузка промокодов',
        ], 'layouts/main.php');
    }

    public function uploadHandle(Req $req, Res $res): Res
    {
        $files = $req->getUploadedFiles();
        $file = $files['file'] ?? null;
        if (!$file instanceof UploadedFileInterface) {
            Flash::add('error', 'Файл не загружен');
            return $res->withHeader('Location', '/dashboard/promo-codes/upload')->withStatus(302);
        }

        try {
            $this->validateCsvFile($file);
        } catch (\Throwable $e) {
            Flash::add('error', $e->getMessage());
            return $res->withHeader('Location', '/dashboard/promo-codes/upload')->withStatus(302);
        }

        $filename = $file->getClientFilename() ?: 'codes.csv';

        $this->db->beginTransaction();
        try {
            // create batch
            $stmt = $this->db->prepare('INSERT INTO promo_code_batches(filename, created_by, total, created_at) VALUES(?, ?, 0, NOW())');
            $stmt->execute([$filename, $_SESSION['user_id'] ?? null]);
            $batchId = (int)$this->db->lastInsertId();

            // parse + insert IGNORE
            $insCount = 0;
            $allCount = 0;
            $chunk = [];
            $stream = $file->getStream()->detach();
            foreach ($this->parseCsv($stream) as $row) {
                $allCount++;
                $code = trim((string)($row['code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $expiresAt = isset($row['expires_at']) && $row['expires_at'] !== '' ? (string)$row['expires_at'] : null;
                $chunk[] = [$batchId, $code, $expiresAt];
                if (count($chunk) >= 500) {
                    $insCount += $this->bulkInsertIgnore($chunk);
                    $chunk = [];
                }
            }
            if ($chunk) {
                $insCount += $this->bulkInsertIgnore($chunk);
            }

            // update batch total
            $u = $this->db->prepare('UPDATE promo_code_batches SET total = ? WHERE id = ?');
            $u->execute([$insCount, $batchId]);

            $this->db->commit();
            $skipped = max(0, $allCount - $insCount);
            Flash::add('success', "Импортировано: {$insCount}, пропущено дублей: {$skipped}");
            return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Flash::add('error', 'Ошибка импорта: ' . $e->getMessage());
            return $res->withHeader('Location', '/dashboard/promo-codes/upload')->withStatus(302);
        }
    }

    public function issue(Req $req, Res $res, array $args): Res
    {
        $codeId = (int)($args['id'] ?? 0);
        $data = (array)$req->getParsedBody();
        $tgUserId = (int)($data['telegram_user_id'] ?? 0);
        if ($codeId <= 0 || $tgUserId <= 0) {
            Flash::add('error', 'Укажите пользователя');
            return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
        }

        // check user exists
        $u = $this->db->prepare('SELECT id FROM telegram_users WHERE user_id = ? LIMIT 1');
        $u->execute([$tgUserId]);
        if (!$u->fetch()) {
            Flash::add('error', 'Пользователь не найден');
            return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, status, expires_at FROM promo_codes WHERE id = ? FOR UPDATE');
            $stmt->execute([$codeId]);
            $row = $stmt->fetch();
            if (!$row) {
                $this->db->rollBack();
                Flash::add('error', 'Код не найден');
                return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
            }
            if ($row['status'] !== 'available') {
                $this->db->rollBack();
                Flash::add('error', 'Код недоступен');
                return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
            }
            if (!empty($row['expires_at']) && strtotime((string)$row['expires_at']) <= time()) {
                $this->db->rollBack();
                Flash::add('error', 'Код просрочен');
                return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
            }

            $upd = $this->db->prepare('UPDATE promo_codes SET status = "issued", issued_at = NOW() WHERE id = ?');
            $upd->execute([$codeId]);

            $ins = $this->db->prepare('INSERT INTO promo_code_issues(code_id, telegram_user_id, issued_by, issued_at) VALUES(?, ?, ?, NOW())');
            $ins->execute([$codeId, $tgUserId, $_SESSION['user_id'] ?? null]);

            $this->db->commit();
            Flash::add('success', 'Код выдан');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            Flash::add('error', 'Не удалось выдать: ' . $e->getMessage());
        }
        return $res->withHeader('Location', '/dashboard/promo-codes')->withStatus(302);
    }

    public function issues(Req $req, Res $res): Res
    {
        $p = $req->getQueryParams();
        $from = (string)($p['from'] ?? '');
        $to = (string)($p['to'] ?? '');
        $tgUserId = isset($p['telegram_user_id']) && $p['telegram_user_id'] !== '' ? (int)$p['telegram_user_id'] : null;
        $limit = min(50, max(20, (int)($p['limit'] ?? 20)));
        $page = max(1, (int)($p['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $conds = [];
        $args = [];
        if ($from !== '') {
            $conds[] = 'i.issued_at >= ?';
            $args[] = $from;
        }
        if ($to !== '') {
            $conds[] = 'i.issued_at <= ?';
            $args[] = $to;
        }
        if ($tgUserId !== null) {
            $conds[] = 'i.telegram_user_id = ?';
            $args[] = $tgUserId;
        }
        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $sql = "SELECT i.id, i.issued_at, i.telegram_user_id, i.issued_by, c.code
                FROM promo_code_issues i
                JOIN promo_codes c ON c.id = i.code_id
                {$where}
                ORDER BY i.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($args as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $cnt = $this->db->prepare("SELECT COUNT(*) FROM promo_code_issues i JOIN promo_codes c ON c.id = i.code_id {$where}");
        foreach ($args as $i => $v) {
            $cnt->bindValue($i + 1, $v);
        }
        $cnt->execute();
        $total = (int)$cnt->fetchColumn();

        return View::render($res, 'dashboard/promo_codes/issues.php', [
            'title' => 'Выдачи промокодов',
            'items' => $items,
            'from' => $from,
            'to' => $to,
            'telegram_user_id' => $tgUserId,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
        ], 'layouts/main.php');
    }

    public function batches(Req $req, Res $res): Res
    {
        $sql = 'SELECT b.id, b.filename, b.created_by, b.created_at, b.total,
                       SUM(c.status = "available") AS available,
                       SUM(c.status = "issued") AS issued,
                       SUM(c.status = "expired") AS expired
                FROM promo_code_batches b
                LEFT JOIN promo_codes c ON c.batch_id = b.id
                GROUP BY b.id
                ORDER BY b.id DESC
                LIMIT 200';
        $rows = $this->db->query($sql)->fetchAll();

        return View::render($res, 'dashboard/promo_codes/batches.php', [
            'title' => 'Батчи промокодов',
            'items' => $rows,
        ], 'layouts/main.php');
    }

    public function exportIssuesCsv(Req $req, Res $res): Res
    {
        $p = $req->getQueryParams();
        $from = (string)($p['from'] ?? '');
        $to = (string)($p['to'] ?? '');
        $tgUserId = isset($p['telegram_user_id']) && $p['telegram_user_id'] !== '' ? (int)$p['telegram_user_id'] : null;

        $conds = [];
        $args = [];
        if ($from !== '') {
            $conds[] = 'i.issued_at >= ?';
            $args[] = $from;
        }
        if ($to !== '') {
            $conds[] = 'i.issued_at <= ?';
            $args[] = $to;
        }
        if ($tgUserId !== null) {
            $conds[] = 'i.telegram_user_id = ?';
            $args[] = $tgUserId;
        }
        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

        $stmt = $this->db->prepare("SELECT i.issued_at, c.code, i.telegram_user_id, i.issued_by
                                     FROM promo_code_issues i
                                     JOIN promo_codes c ON c.id = i.code_id
                                     {$where}
                                     ORDER BY i.id DESC
                                     LIMIT 10000");
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        $filename = 'issues_' . date('Ymd_His') . '.csv';
        $res = $res
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['issued_at', 'code', 'telegram_user_id', 'issued_by']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['issued_at'], $r['code'], $r['telegram_user_id'], $r['issued_by']]);
        }
        rewind($out);
        $res->getBody()->write(stream_get_contents($out) ?: '');
        fclose($out);
        return $res;
    }

    private function getDb(): PDO
    {
        return $this->db;
    }

    private function validateCsvFile(UploadedFileInterface $file): void
    {
        $sizeLimit = (int)($_ENV['REQUEST_SIZE_LIMIT'] ?? 1048576);
        $maxUpload = min($sizeLimit, 5 * 1024 * 1024); // 5MB
        if (($file->getSize() ?? 0) > $maxUpload) {
            throw new \RuntimeException('Слишком большой файл (макс. 5MB)');
        }
        $allowed = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
        $type = (string)($file->getClientMediaType() ?? '');
        if ($type !== '' && !in_array($type, $allowed, true)) {
            throw new \RuntimeException('Недопустимый тип файла');
        }
        $name = (string)($file->getClientFilename() ?? '');
        if ($name !== '' && !preg_match('~\.csv($|\?)~i', $name)) {
            // допускаем .csv, остальное считаем подозрительным
            throw new \RuntimeException('Ожидается CSV-файл (.csv)');
        }
    }

    /**
     * Ленивый парсер CSV. Первая строка — заголовок. Поддерживает колонки: code, expires_at, meta.
     *
     * @param resource $stream
     * @return iterable<array{code:string,expires_at?:string,meta?:string}>
     */
    private function parseCsv($stream): iterable
    {
        if (!is_resource($stream)) {
            throw new \RuntimeException('Invalid stream');
        }
        $header = null;
        $map = [];
        while (($row = fgetcsv($stream)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }
            if ($header === null) {
                $header = array_map(static fn ($v) => strtolower(trim((string)$v)), $row);
                foreach ($header as $i => $name) {
                    $map[$name] = $i;
                }
                if (!array_key_exists('code', $map)) {
                    throw new \RuntimeException('Нет колонки code в заголовке');
                }
                continue;
            }
            $get = static function (string $name) use ($row, $map): ?string {
                if (!array_key_exists($name, $map)) {
                    return null;
                }
                $val = $row[$map[$name]] ?? null;
                return $val !== null ? trim((string)$val) : null;
            };
            $code = (string)($get('code') ?? '');
            if ($code === '') {
                continue;
            }
            yield [
                'code' => $code,
                'expires_at' => $get('expires_at') ?? '',
                'meta' => $get('meta') ?? '',
            ];
        }
    }

    private function bulkInsertIgnore(array $chunk): int
    {
        // chunk: [ [batch_id, code, expires_at], ... ]
        if ($chunk === []) {
            return 0;
        }
        $vals = [];
        $args = [];
        foreach ($chunk as [$batchId, $code, $expiresAt]) {
            $vals[] = '(?, ?, "available", ?)';
            $args[] = (int)$batchId;
            $args[] = (string)$code;
            $args[] = $expiresAt !== null && $expiresAt !== '' ? $expiresAt : null;
        }
        $sql = 'INSERT IGNORE INTO promo_codes(batch_id, code, status, expires_at) VALUES ' . implode(',', $vals);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->rowCount();
    }

    private function flash(string $type, string $message): void
    {
        Flash::add($type, $message);
    }
}
