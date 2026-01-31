<?php

declare(strict_types=1);

use Dotenv\Dotenv;

Dotenv::createImmutable(__DIR__)->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

$dsn = $_ENV['DB_DSN'] ?? "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
$adapter = explode(':', $dsn, 2)[0] ?: 'mysql';

$db = [
    'adapter' => $adapter,
    'host' => $host,
    'port' => $port,
    'name' => $dbname !== '' ? $dbname : null,
    'user' => $_ENV['DB_USER'] ?? null,
    'pass' => $_ENV['DB_PASS'] ?? null,
    'charset' => $charset,
];

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => $_ENV['APP_ENV'] ?? 'dev',
        'dev' => $db,
        'prod' => $db,
    ],
];

