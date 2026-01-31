<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChannelPostsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `channel_posts` (
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор канала',
            `message_id` BIGINT NOT NULL COMMENT 'Идентификатор сообщения',
            `date` TIMESTAMP NOT NULL COMMENT 'Дата и время публикации',
            `author_signature` TEXT NULL COMMENT 'Подпись автора сообщения',
            `text` TEXT NULL COMMENT 'Текст сообщения',
            `caption` TEXT NULL COMMENT 'Подпись к медиа',
            `entities` JSON NULL COMMENT 'Сущности сообщения в формате JSON',
            `media` JSON NULL COMMENT 'Данные о медиа в формате JSON',
            `is_automatic_forward` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Признак автоматической пересылки',
            `forward_from_chat` JSON NULL COMMENT 'Данные об исходном чате пересылки',
            `link_preview_options` JSON NULL COMMENT 'Настройки предпросмотра ссылок',
            PRIMARY KEY (`chat_id`, `message_id`),
            CHECK (JSON_VALID(entities)),
            CHECK (JSON_VALID(media)),
            CHECK (JSON_VALID(forward_from_chat)),
            CHECK (JSON_VALID(link_preview_options))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

