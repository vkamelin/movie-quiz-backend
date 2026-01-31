<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterTsMessagesAddStatus extends AbstractMigration
{
    public function up(): void
    {
        // Add status and timestamps to track lifecycle of scheduled messages
        $this->execute("ALTER TABLE `telegram_scheduled_messages` 
            ADD COLUMN `status` ENUM('pending','processing','canceled') NOT NULL DEFAULT 'pending' AFTER `send_after`,
            ADD COLUMN `started_at` TIMESTAMP NULL DEFAULT NULL AFTER `status`,
            ADD COLUMN `canceled_at` TIMESTAMP NULL DEFAULT NULL AFTER `started_at`,
            ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`
        ");
    }
}

