<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use Phinx\Console\Command\Migrate as PhinxMigrate;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Команда запуска миграций базы данных.
 */
class MigrateRunCommand extends Command
{
    public string $signature = 'migrate:run';
    public string $description = 'Запустить миграции';

    /**
     * Выполняет миграции через Phinx.
     *
     * @param array<int,string> $arguments Аргументы команды (не используются)
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода Phinx
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $application = new PhinxApplication();
        $command = new PhinxMigrate();
        $command->setApplication($application);

        $config = dirname(__DIR__, 3) . '/phinx.php';
        $env = $_ENV['APP_ENV'] ?? 'dev';

        $input = new ArrayInput([
            '--configuration' => $config,
            '--environment' => $env,
        ]);
        $output = new ConsoleOutput();

        return $command->run($input, $output);
    }
}
