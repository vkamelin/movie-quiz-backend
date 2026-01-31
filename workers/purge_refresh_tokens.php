<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Helpers\Database;
use App\Helpers\RefreshTokenService;

$svc = new RefreshTokenService(Database::getInstance());
$count = $svc->purgeExpired();
echo "Purged {$count} expired tokens." . PHP_EOL;
