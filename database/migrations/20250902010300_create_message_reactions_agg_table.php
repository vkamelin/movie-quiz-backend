<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMessageReactionsAggTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `message_reactions_agg` (
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `message_id` BIGINT NOT NULL COMMENT 'Идентификатор сообщения',
            `agg` JSON NOT NULL COMMENT 'Агрегированный список реакций в формате JSON',
            `updated_at` TIMESTAMP NOT NULL COMMENT 'Дата и время обновления счётчиков',
            PRIMARY KEY (`chat_id`, `message_id`),
            CHECK (JSON_VALID(agg))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

