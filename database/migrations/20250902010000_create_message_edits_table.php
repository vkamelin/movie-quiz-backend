<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMessageEditsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `message_edits` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор правки',
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `message_id` BIGINT NOT NULL COMMENT 'Идентификатор сообщения',
            `editor_user_id` BIGINT NULL COMMENT 'Пользователь, внесший изменение',
            `edit_date` TIMESTAMP NOT NULL COMMENT 'Дата и время редактирования',
            `new_text` TEXT NULL COMMENT 'Новый текст сообщения',
            `new_caption` TEXT NULL COMMENT 'Новая подпись медиа',
            `entities` JSON NULL COMMENT 'Новые сущности сообщения в формате JSON',
            `media` JSON NULL COMMENT 'Информация о медиа в формате JSON',
            `is_channel` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Признак правки канального поста',
            UNIQUE KEY `uniq_chat_msg_edit` (`chat_id`, `message_id`, `edit_date`),
            CHECK (JSON_VALID(entities)),
            CHECK (JSON_VALID(media))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

