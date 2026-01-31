<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIdxChatJoinRequestsStatus extends AbstractMigration
{
    public function up(): void
    {
        $exists = $this->fetchRow("SHOW INDEX FROM `chat_join_requests` WHERE Key_name = 'idx_chat_join_requests_status'");
        if (! $exists) {
            $this->execute("CREATE INDEX `idx_chat_join_requests_status` ON `chat_join_requests` (`status`);");
        }
    }
}
