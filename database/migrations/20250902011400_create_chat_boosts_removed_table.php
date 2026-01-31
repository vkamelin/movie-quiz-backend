<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChatBoostsRemovedTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `chat_boosts_removed` (
            `id` VARCHAR(255) PRIMARY KEY COMMENT 'Идентификатор снятого буста',
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `reason` JSON NULL COMMENT 'Причина снятия буста в формате JSON',
            `removed_at` TIMESTAMP NOT NULL COMMENT 'Дата и время снятия буста',
            `payload` JSON NULL COMMENT 'Сырой объект события',
            CHECK (JSON_VALID(reason)),
            CHECK (JSON_VALID(payload))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

