<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInlineChosenResultsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `inline_chosen_results` (
            `result_id` VARCHAR(255) PRIMARY KEY COMMENT 'Идентификатор выбранного результата',
            `from_user_id` BIGINT NOT NULL COMMENT 'Идентификатор пользователя, выбравшего результат',
            `query` TEXT NULL COMMENT 'Исходный текст запроса',
            `inline_message_id` TEXT NULL COMMENT 'Идентификатор inline-сообщения, если отправлено',
            `location` JSON NULL COMMENT 'Геолокация пользователя в формате JSON',
            `chosen_at` TIMESTAMP NOT NULL COMMENT 'Дата и время выбора результата',
            CHECK (JSON_VALID(location))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

