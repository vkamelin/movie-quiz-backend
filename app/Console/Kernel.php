<?php

namespace App\Console;

use App\Console\Commands\ImportMoviesCommand;

/**
 * Ядро консольного приложения.
 *
 * Хранит список доступных команд и отвечает за их запуск.
 */
class Kernel
{
    /**
     * @var array<class-string<Command>>
     */
    protected array $commands = [
        Commands\ListCommand::class,
        Commands\HelpCommand::class,
        Commands\CacheClearCommand::class,
        Commands\MigrateRunCommand::class,
        Commands\MigrateCreateCommand::class,
        Commands\MigrateRollbackCommand::class,
        Commands\RefreshTokenPurgeCommand::class,
        Commands\CreateAdminCommand::class,
        Commands\UpdateFilterCommand::class,
        Commands\WorkerHandlerCommand::class,
        Commands\PushSendCommand::class,
        Commands\ScheduledDispatchCommand::class,
        Commands\StreamInitCommand::class,
        Commands\StreamStatsCommand::class,
        Commands\StreamResetCommand::class,
        Commands\StreamDlqCommand::class,
        Commands\SupervisorNotificationsCommand::class,
        Commands\ImportMoviesCommand::class
    ];

    /**
     * Инстанцирует доступные команды.
     *
     * @return array<Command> Список подготовленных к исполнению команд
     */
    public function commands(): array
    {
        return array_map(static fn (string $class): Command => new $class(), $this->commands);
    }

    /**
     * Обрабатывает входные аргументы и запускает нужную команду.
     *
     * @param array<int,string> $argv Аргументы командной строки
     * @return int Код выхода (0 — успех)
     */
    public function handle(array $argv): int
    {
        $name = $argv[1] ?? 'list';
        foreach ($this->commands as $class) {
            $command = new $class();
            if ($command->signature === $name) {
                return $command->run(array_slice($argv, 2), $this);
            }
        }

        echo "Command \"{$name}\" not found." . PHP_EOL;
        return 1;
    }
}
