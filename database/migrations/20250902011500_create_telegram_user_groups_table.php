<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramUserGroupsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_user_groups` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Group identifier',
            `name` VARCHAR(255) NOT NULL COMMENT 'Group name',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation date'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
    }
}
