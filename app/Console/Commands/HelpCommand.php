<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;

/**
 * Команда справки по другим командам.
 */
class HelpCommand extends Command
{
    public string $signature = 'help';

    /**
     * Показывает описание указанной команды.
     *
     * @param array<int,string> $arguments Аргументы команды (имя целевой команды)
     * @param Kernel $kernel Ядро для доступа к списку команд
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $name = $arguments[0] ?? null;
        if (!$name) {
            echo 'Usage: help <command>' . PHP_EOL;
            return 0;
        }

        foreach ($kernel->commands() as $cmd) {
            if ($cmd->signature === $name) {
                echo "{$cmd->signature} - {$cmd->description}" . PHP_EOL;
                return 0;
            }
        }

        echo "Command \"{$name}\" not found." . PHP_EOL;
        return 1;
    }
}
