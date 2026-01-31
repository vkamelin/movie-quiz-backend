#!/usr/bin/env php
<?php
/**
 * Supervisor event listener для уведомлений о падениях процессов.
 *
 * Слушает события PROCESS_STATE_EXITED и PROCESS_STATE_FATAL от supervisor
 * и записывает уведомления в Redis для мониторинга.
 *
 * Supervisor посылает события в формате:
 * headers\n
 * data\n
 */

declare(strict_types=1);

use App\Helpers\RedisHelper;
use App\Helpers\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

// Читаем события из stdin
$raw = '';
while ($line = fgets(STDIN)) {
    $raw .= $line;
}

if (empty($raw)) {
    exit(0);
}

// Парсим событие
$parts = explode("\n", trim($raw), 2);
if (count($parts) < 2) {
    exit(0);
}

$headers = $parts[0];
$data = $parts[1];

// Извлекаем заголовки
$headersArray = [];
foreach (explode(' ', $headers) as $header) {
    if (str_contains($header, ':')) {
        [$key, $value] = explode(':', $header, 2);
        $headersArray[trim($key)] = trim($value);
    }
}

// Проверяем, что это событие выхода
$eventName = $headersArray['eventname'] ?? '';
if (!str_starts_with($eventName, 'PROCESS_STATE_')) {
    exit(0);
}

// Парсим данные
$dataArray = [];
parse_str(trim($data), $dataArray);

$processName = $dataArray['processname'] ?? 'unknown';
$pid = $dataArray['pid'] ?? 'unknown';
$exitCode = $dataArray['exitcode'] ?? 'unknown';
$expected = $dataArray['expected'] ?? '0';

// Формируем уведомление
$notification = [
    'event' => $eventName,
    'process' => $processName,
    'pid' => $pid,
    'exit_code' => $exitCode,
    'expected' => $expected === '1',
    'timestamp' => time(),
    'hostname' => gethostname() ?: 'unknown',
];

// Записываем в Redis
try {
    $redis = RedisHelper::getInstance();
    $redis->lpush('supervisor:notifications', json_encode($notification, JSON_UNESCAPED_UNICODE));

    // Ограничиваем размер списка
    $redis->ltrim('supervisor:notifications', 0, 100);

    // Если это неожиданное падение — логируем
    if ($expected !== '1') {
        Logger::warning('Process crashed', [
            'process' => $processName,
            'pid' => $pid,
            'exit_code' => $exitCode,
        ]);
    }
} catch (\Exception $e) {
    // Не можем записать в Redis — просто выходим
    fwrite(STDERR, "Failed to write notification: " . $e->getMessage() . "\n");
}

echo "RESULT\n";
exit(0);