<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Идентификатор пользователя',
            `email` VARCHAR(320) DEFAULT NULL COMMENT 'Email пользователя',
            `password` VARCHAR(255) DEFAULT NULL COMMENT 'Пароль пользователя',
            `telegram_user_id` BIGINT DEFAULT NULL COMMENT 'Telegram ID пользователя',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания записи',
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления записи'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE UNIQUE INDEX `idx_users_email` ON `users` (`email`);");
    }
}
