<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterTelegramMessagesAddScheduledId extends AbstractMigration
{
    public function up(): void
    {
        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_messages` LIKE 'scheduled_id'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_messages` ADD COLUMN `scheduled_id` BIGINT UNSIGNED NULL AFTER `type`");
        }

        $exists = $this->fetchRow("SHOW INDEX FROM `telegram_messages` WHERE Key_name = 'idx_tm_scheduled_id'");
        if (!$exists) {
            $this->execute("CREATE INDEX `idx_tm_scheduled_id` ON `telegram_messages` (`scheduled_id`)");
        }
    }
}
