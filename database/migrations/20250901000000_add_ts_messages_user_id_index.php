<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTsMessagesUserIdIndex extends AbstractMigration
{
    public function up(): void
    {
        $exists = $this->fetchRow("SHOW INDEX FROM `telegram_scheduled_messages` WHERE Key_name = 'idx_ts_messages_user_id'");
        if (!$exists) {
            $this->execute("CREATE INDEX `idx_ts_messages_user_id` ON `telegram_scheduled_messages` (`user_id`);");
        }
    }
}
