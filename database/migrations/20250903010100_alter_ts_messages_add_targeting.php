<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterTsMessagesAddTargeting extends AbstractMigration
{
    public function up(): void
    {
        // Add targeting columns for batch scheduling and basic reporting counters (idempotent)
        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'target_type'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `target_type` ENUM('all','group','selected') NULL AFTER `status`");
        }

        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'target_group_id'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `target_group_id` INT UNSIGNED NULL AFTER `target_type`");
        }

        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'selected_count'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `selected_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `target_group_id`");
        }

        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'success_count'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `success_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `selected_count`");
        }

        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'failed_count'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `failed_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `success_count`");
        }

        // Helpful indexes (MySQL 5.7 compatible)
        $exists = $this->fetchRow("SHOW INDEX FROM `telegram_scheduled_messages` WHERE Key_name = 'idx_ts_messages_target_type'");
        if (!$exists) {
            $this->execute("CREATE INDEX `idx_ts_messages_target_type` ON `telegram_scheduled_messages` (`target_type`)");
        }

        $exists = $this->fetchRow("SHOW INDEX FROM `telegram_scheduled_messages` WHERE Key_name = 'idx_ts_messages_target_group_id'");
        if (!$exists) {
            $this->execute("CREATE INDEX `idx_ts_messages_target_group_id` ON `telegram_scheduled_messages` (`target_group_id`)");
        }
    }
}
