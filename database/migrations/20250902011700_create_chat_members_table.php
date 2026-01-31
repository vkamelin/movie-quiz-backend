<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChatMembersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `chat_members` (
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя',
            `role` VARCHAR(50) NULL COMMENT 'Роль пользователя в чате',
            `state` VARCHAR(20) NOT NULL COMMENT 'Состояние участника: approved/declined',
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления',
            PRIMARY KEY (`chat_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}
