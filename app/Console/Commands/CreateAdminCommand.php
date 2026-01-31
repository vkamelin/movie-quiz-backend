<?php

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;

/**
 * Команда создания администратора панели.
 */
class CreateAdminCommand extends Command
{
    public string $signature = 'admin:create';
    public string $description = 'Create dashboard administrator';

    /**
     * Создаёт пользователя-администратора с указанными email и паролем.
     *
     * @param array<int,string> $arguments [email, password]
     * @param Kernel $kernel Ядро (не используется)
     * @return int Код выхода
     */
    public function handle(array $arguments, Kernel $kernel): int
    {
        $email = $arguments[0] ?? trim(readline('Email: '));
        $password = $arguments[1] ?? trim(readline('Password: '));

        if ($email === '' || $password === '') {
            echo 'Email and password are required.' . PHP_EOL;
            return 1;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO users (email, password) VALUES (:email, :password)');
        $stmt->execute(['email' => $email, 'password' => $hash]);

        echo 'Administrator created.' . PHP_EOL;
        return 0;
    }
}
