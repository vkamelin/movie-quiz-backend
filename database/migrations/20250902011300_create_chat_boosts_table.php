<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChatBoostsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `chat_boosts` (
            `id` VARCHAR(255) PRIMARY KEY COMMENT 'Идентификатор буста',
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `source` JSON NULL COMMENT 'Источник буста в формате JSON',
            `start_at` TIMESTAMP NULL COMMENT 'Начало действия буста',
            `end_at` TIMESTAMP NULL COMMENT 'Окончание действия буста',
            `payload` JSON NULL COMMENT 'Сырой объект буста',
            `received_at` TIMESTAMP NOT NULL COMMENT 'Время получения события',
            CHECK (JSON_VALID(source)),
            CHECK (JSON_VALID(payload))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

