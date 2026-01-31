<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChatMemberUpdatesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `chat_member_updates` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор записи',
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `target_user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя, чьи права изменены',
            `actor_user_id` BIGINT NULL COMMENT 'Администратор, изменивший права',
            `old_status` VARCHAR(50) NULL COMMENT 'Предыдущий статус участника',
            `new_status` VARCHAR(50) NULL COMMENT 'Новый статус участника',
            `rights` JSON NULL COMMENT 'Права участника в формате JSON',
            `until_date` TIMESTAMP NULL COMMENT 'Срок действия прав, если ограничены',
            `changed_at` TIMESTAMP NOT NULL COMMENT 'Дата и время изменения',
            CHECK (JSON_VALID(rights))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

