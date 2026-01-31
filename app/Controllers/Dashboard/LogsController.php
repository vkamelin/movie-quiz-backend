<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Path;
use App\Helpers\Response;
use App\Helpers\View;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

final class LogsController
{
    private string $logsDir;

    public function __construct()
    {
        $this->logsDir = rtrim(Path::base('storage/logs'), '/\\');
    }

    public function index(Req $req, Res $res): Res
    {
        return View::render($res, 'dashboard/logs/index.php', [
            'title' => 'Логи',
        ], 'layouts/main.php');
    }

    public function files(Req $req, Res $res): Res
    {
        $files = [];
        foreach (glob($this->logsDir . '/app-*.log') as $path) {
            $files[] = [
                'name' => basename($path),
                'size' => filesize($path) ?: 0,
                'mtime' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
            ];
        }
        // Sort by name desc (dates newer first)
        usort($files, static fn ($a, $b) => strcmp($b['name'], $a['name']));

        return Response::json($res, 200, ['files' => $files]);
    }

    public function data(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $start = max(0, (int)($p['start'] ?? 0));
        $length = (int)($p['length'] ?? 10);
        $draw = (int)($p['draw'] ?? 0);
        $search = (string)($p['search']['value'] ?? '');
        $level = (string)($p['level'] ?? '');
        $file = basename((string)($p['file'] ?? ''));
        if ($file === '') {
            // Fallback to query param (when returning from details with ?file=...)
            $q = $req->getQueryParams();
            $file = basename((string)($q['file'] ?? ''));
        }
        if ($file === '') {
            // As a last resort, pick the newest app-*.log
            $candidates = glob($this->logsDir . '/app-*.log') ?: [];
            if ($candidates !== []) {
                usort($candidates, static fn ($a, $b) => strcmp(basename($b), basename($a)));
                $file = basename($candidates[0]);
            }
        }
        $path = $this->logsDir . '/' . $file;

        if ($file === '' || !is_file($path)) {
            return Response::json($res, 200, [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        // Read and parse lines (limit to last 50000 lines for safety)
        $rows = $this->readLogLines($path, 50000);

        // Filtering
        $filtered = array_filter($rows, static function (array $row) use ($level, $search): bool {
            if ($level !== '' && strcasecmp($row['level_name'] ?? '', $level) !== 0) {
                return false;
            }
            if ($search !== '') {
                $hay = strtolower(
                    ($row['message'] ?? '') . ' ' .
                    (($row['context_exception_message'] ?? '')) . ' ' .
                    (($row['channel'] ?? ''))
                );
                if (!str_contains($hay, strtolower($search))) {
                    return false;
                }
            }
            return true;
        });

        $recordsTotal = count($rows);
        $recordsFiltered = count($filtered);

        // Sorting: default by datetime desc
        $orderDir = 'desc';
        if (!empty($p['order'][0]['dir'])) {
            $dir = strtolower($p['order'][0]['dir']);
            $orderDir = ($dir === 'asc') ? 'asc' : 'desc';
        }
        usort($filtered, static function (array $a, array $b) use ($orderDir): int {
            $ta = strtotime($a['datetime'] ?? '') ?: 0;
            $tb = strtotime($b['datetime'] ?? '') ?: 0;
            return $orderDir === 'asc' ? ($ta <=> $tb) : ($tb <=> $ta);
        });

        $data = array_slice(array_values($filtered), $start, $length > 0 ? $length : null);

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Детальный просмотр одной записи лога по номеру строки.
     */
    public function show(Req $req, Res $res): Res
    {
        $q = $req->getQueryParams();
        $file = basename((string)($q['file'] ?? ''));
        $lineNo = (int)($q['line'] ?? 0);
        $path = $this->logsDir . '/' . $file;
        if ($file === '' || !is_file($path) || $lineNo <= 0) {
            return $res->withStatus(404);
        }
        $rows = $this->readLogLines($path, 50000);
        $item = null;
        foreach ($rows as $r) {
            if ((int)($r['line_no'] ?? 0) === $lineNo) {
                $item = $r;
                break;
            }
        }
        if ($item === null) {
            return $res->withStatus(404);
        }

        return View::render($res, 'dashboard/logs/view.php', [
            'title' => 'Лог: ' . $file,
            'file' => $file,
            'item' => $item,
        ], 'layouts/main.php');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readLogLines(string $path, int $limitLines = 50000): array
    {
        $rows = [];
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return $rows;
        }
        $count = 0;
        $lineNo = 0;
        while (!feof($fh)) {
            $line = fgets($fh);
            if ($line === false) {
                break;
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $lineNo++;
            $row = $this->parseLogLine($line);
            $row['line_no'] = $lineNo;
            $row['raw'] = $line;
            $rows[] = $row;
            $count++;
            if ($count >= $limitLines) {
                break;
            }
        }
        fclose($fh);
        return $rows;
    }

    /**
     * @return array<string,mixed>
     */
    private function parseLogLine(string $line): array
    {
        $row = [
            'datetime' => '',
            'level_name' => '',
            'channel' => '',
            'message' => $line,
            'context_exception_class' => '',
            'context_exception_message' => '',
            'request_id' => '',
        ];
        if ($line !== '' && $line[0] === '{') {
            $data = json_decode($line, true);
            if (is_array($data)) {
                $row['message'] = (string)($data['message'] ?? '');
                $row['level_name'] = (string)($data['level_name'] ?? '');
                $row['channel'] = (string)($data['channel'] ?? '');
                $row['datetime'] = (string)($data['datetime'] ?? '');
                $row['request_id'] = (string)($data['extra']['request_id'] ?? ($data['extra']['uid'] ?? ''));
                $ex = $data['context']['exception'] ?? null;
                if (is_array($ex)) {
                    $row['context_exception_class'] = (string)($ex['class'] ?? '');
                    $row['context_exception_message'] = (string)($ex['message'] ?? '');
                }
            }
        }
        return $row;
    }
}
