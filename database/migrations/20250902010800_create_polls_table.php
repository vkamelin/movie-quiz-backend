<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePollsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `polls` (
            `poll_id` VARCHAR(255) PRIMARY KEY COMMENT 'Уникальный идентификатор опроса',
            `question` TEXT NOT NULL COMMENT 'Вопрос опроса',
            `is_anonymous` BOOLEAN NOT NULL COMMENT 'Признак анонимности опроса',
            `allows_multiple_answers` BOOLEAN NOT NULL COMMENT 'Разрешены ли множественные ответы',
            `options` JSON NOT NULL COMMENT 'Варианты ответов в формате JSON',
            `open_period` INT NULL COMMENT 'Время в секундах, в течение которого опрос открыт',
            `close_date` TIMESTAMP NULL COMMENT 'Время автоматического закрытия опроса',
            `total_voter_count` INT NOT NULL DEFAULT 0 COMMENT 'Текущее количество проголосовавших',
            `created_at` TIMESTAMP NOT NULL COMMENT 'Время создания опроса',
            CHECK (JSON_VALID(options))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

