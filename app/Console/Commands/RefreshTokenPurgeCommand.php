<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\RefreshTokenService;

/**
 * Команда очистки просроченных refresh-токенов.
 */
class RefreshTokenPurgeCommand extends Command
{
    public string $signature = 'tokens:purge';
    public string $description = 'Remove expired refresh tokens';

    /**
     * Удаляет просроченные refresh-токены из БД.
     *
     * @param array<int,string> $arguments Аргументы команды (не используются)
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $svc = new RefreshTokenService(Database::getInstance());
        $count = $svc->purgeExpired();
        echo "Purged {$count} expired tokens." . PHP_EOL;
        return 0;
    }
}
