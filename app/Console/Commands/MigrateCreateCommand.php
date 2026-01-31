<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use Phinx\Console\Command\Create as PhinxCreate;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Команда создания файла миграции.
 */
class MigrateCreateCommand extends Command
{
    public string $signature = 'migrate:create';
    public string $description = 'Создать новую миграцию';

    /**
     * Создаёт новый файл миграции через Phinx.
     *
     * @param array<int,string> $arguments [name] — имя миграции
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода Phinx
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $name = $arguments[0] ?? null;
        if ($name === null || $name === '') {
            echo 'Migration name required.' . PHP_EOL;
            return 1;
        }

        $application = new PhinxApplication();
        $command = new PhinxCreate();
        $command->setApplication($application);

        $config = dirname(__DIR__, 3) . '/phinx.php';

        $input = new ArrayInput([
            'name' => $name,
            '--configuration' => $config,
        ]);
        $output = new ConsoleOutput();

        return $command->run($input, $output);
    }
}
