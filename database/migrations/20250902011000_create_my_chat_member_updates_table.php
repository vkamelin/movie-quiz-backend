<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMyChatMemberUpdatesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `my_chat_member_updates` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор записи',
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `actor_user_id` BIGINT NULL COMMENT 'Пользователь, изменивший права бота',
            `old_status` VARCHAR(50) NULL COMMENT 'Старый статус бота в чате',
            `new_status` VARCHAR(50) NULL COMMENT 'Новый статус бота в чате',
            `new_rights` JSON NULL COMMENT 'Новые права бота в формате JSON',
            `until_date` TIMESTAMP NULL COMMENT 'Срок действия прав, если ограничены',
            `changed_at` TIMESTAMP NOT NULL COMMENT 'Дата и время изменения статуса',
            CHECK (JSON_VALID(new_rights))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

