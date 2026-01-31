<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramSessionsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_sessions` (
            `user_id` BIGINT UNSIGNED PRIMARY KEY COMMENT 'Telegram user id',
            `state` VARCHAR(32) DEFAULT NULL COMMENT 'Session state',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation time',
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Update time'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}
