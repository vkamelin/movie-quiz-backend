<?php

namespace App\Console;

use Dotenv\Dotenv;

/**
 * Базовый класс консольных команд.
 *
 * Определяет контракт выполнения команды и загрузку окружения перед запуском.
 */
abstract class Command
{
    public string $signature = '';
    public string $description = '';

    /**
     * Обработчик команды.
     *
     * @param array<int,string> $arguments Аргументы команды (без имени команды)
     * @param Kernel $kernel Ядро консольного приложения
     * @return int Код выхода (0 — успех)
     */
    abstract public function handle(array $arguments, Kernel $kernel): int;

    /**
     * Запуск команды с предварительной загрузкой переменных окружения.
     *
     * @param array<int,string> $arguments Аргументы команды (без имени команды)
     * @param Kernel $kernel Ядро консольного приложения
     * @return int Код выхода
     */
    public function run(array $arguments, Kernel $kernel): int
    {
        Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

        return $this->handle($arguments, $kernel);
    }
}
