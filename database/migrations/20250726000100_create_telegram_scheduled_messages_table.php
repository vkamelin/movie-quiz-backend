<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramScheduledMessagesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_scheduled_messages` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` BIGINT UNSIGNED DEFAULT NULL,
            `method` VARCHAR(255) NOT NULL,
            `type` VARCHAR(255) DEFAULT NULL,
            `data` JSON NOT NULL,
            `priority` TINYINT(1) DEFAULT 0,
            `send_after` TIMESTAMP NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CHECK (JSON_VALID(data))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_ts_messages_user_id` ON `telegram_scheduled_messages` (`user_id`);");
        $this->execute("CREATE INDEX `idx_ts_messages_send_after` ON `telegram_scheduled_messages` (`send_after`);");
        $this->execute("CREATE INDEX `idx_ts_messages_priority` ON `telegram_scheduled_messages` (`priority`);");
    }
}
