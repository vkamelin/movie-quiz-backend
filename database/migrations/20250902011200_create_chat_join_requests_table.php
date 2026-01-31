<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChatJoinRequestsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `chat_join_requests` (
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя, подавшего заявку',
            `bio` TEXT NULL COMMENT 'Био пользователя',
            `invite_link` JSON NULL COMMENT 'Данные инвайт-ссылки в формате JSON',
            `requested_at` TIMESTAMP NOT NULL COMMENT 'Дата и время подачи заявки',
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Статус заявки',
            `decided_at` TIMESTAMP NULL COMMENT 'Дата и время решения по заявке',
            `decided_by` BIGINT NULL COMMENT 'Администратор, принявший решение',
            PRIMARY KEY (`chat_id`, `user_id`),
            CHECK (JSON_VALID(invite_link))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

