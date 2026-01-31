<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMessageReactionsUserTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `message_reactions_user` (
            `chat_id` BIGINT NOT NULL COMMENT 'Идентификатор чата',
            `message_id` BIGINT NOT NULL COMMENT 'Идентификатор сообщения',
            `user_id` BIGINT NOT NULL COMMENT 'Пользователь, оставивший реакцию',
            `reactions` JSON NOT NULL COMMENT 'Текущий набор реакций пользователя в формате JSON',
            `updated_at` TIMESTAMP NOT NULL COMMENT 'Дата и время обновления реакции',
            PRIMARY KEY (`chat_id`, `message_id`, `user_id`),
            CHECK (JSON_VALID(reactions))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}

