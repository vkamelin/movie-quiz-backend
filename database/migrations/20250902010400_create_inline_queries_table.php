<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInlineQueriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `inline_queries` (
            `inline_query_id` VARCHAR(255) PRIMARY KEY COMMENT 'Уникальный идентификатор inline-запроса',
            `from_user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя, отправившего запрос',
            `query` TEXT NOT NULL COMMENT 'Текст inline-запроса',
            `offset` TEXT NULL COMMENT 'Смещение для постраничной выдачи',
            `location` JSON NULL COMMENT 'Геолокация пользователя в формате JSON',
            `language_code` VARCHAR(10) NULL COMMENT 'Язык пользователя',
            `received_at` TIMESTAMP NOT NULL COMMENT 'Время получения запроса',
            CHECK (JSON_VALID(location))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

