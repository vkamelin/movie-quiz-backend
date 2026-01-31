<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;

/**
 * Команда для вывода списка доступных команд.
 */
class ListCommand extends Command
{
    public string $signature = 'list';
    public string $description = 'Список доступных команд';

    /**
     * Выводит список зарегистрированных команд.
     *
     * @param array<int,string> $arguments Аргументы команды (не используются)
     * @param Kernel $kernel Ядро для доступа к командам
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        echo 'Доступные команды:' . PHP_EOL;
        foreach ($kernel->commands() as $cmd) {
            echo "  {$cmd->signature}\t{$cmd->description}" . PHP_EOL;
        }
        return 0;
    }
}
