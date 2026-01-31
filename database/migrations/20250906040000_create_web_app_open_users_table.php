<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWebAppOpenUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `web_app_open_users` (
            `user_id` BIGINT UNSIGNED NOT NULL,
            `opened_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");

        $this->execute("CREATE INDEX `idx_web_app_open_users_opened_at` ON `web_app_open_users` (`opened_at`);");
    }
}

