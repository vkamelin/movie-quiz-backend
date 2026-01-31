<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePollAnswersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `poll_answers` (
            `poll_id` VARCHAR(255) NOT NULL COMMENT 'Идентификатор опроса',
            `user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя',
            `option_ids` JSON NOT NULL COMMENT 'Массив индексов выбранных опций',
            `answered_at` TIMESTAMP NOT NULL COMMENT 'Дата и время ответа',
            PRIMARY KEY (`poll_id`, `user_id`),
            CHECK (JSON_VALID(option_ids))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

