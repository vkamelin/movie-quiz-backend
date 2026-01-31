<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterTsMessagesAddCompletedStatus extends AbstractMigration
{
    public function up(): void
    {
        // Extend enum to include 'completed' if not present
        $col = $this->fetchRow("SHOW COLUMNS FROM `telegram_scheduled_messages` LIKE 'status'");
        if (!$col) {
            return; // table or column not found in this environment
        }
        $type = (string)($col['Type'] ?? '');
        if (stripos($type, "'completed'") !== false) {
            return; // already has completed
        }

        // MySQL ALTER to extend enum values
        $this->execute("ALTER TABLE `telegram_scheduled_messages` MODIFY COLUMN `status` ENUM('pending','processing','canceled','completed') NOT NULL DEFAULT 'pending'");
    }
}

