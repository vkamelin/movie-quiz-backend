<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterTsMessagesAddCompletedAt extends AbstractMigration
{
    public function up(): void
    {
        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'completed_at'");
        if (!$col) {
            $this->execute("ALTER TABLE `telegram_scheduled_messages` ADD COLUMN `completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `canceled_at`");
        }
    }
}

