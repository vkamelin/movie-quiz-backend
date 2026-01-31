<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramMessagesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_messages` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор записи',
            `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Идентификатор чата (chat_id)',
            `method` VARCHAR(255) NOT NULL COMMENT 'Метод, вызываемый в Telegram Bot API',
            `type` VARCHAR(255) DEFAULT NULL COMMENT 'Тип запроса. Для распознавания отдельных рассылок, запросов и т.д.',
            `data` JSON NOT NULL COMMENT 'Данные, передаваемые в Telegram Bot API',
            `message_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Идентификатор сообщения в Telegram',
            `priority` TINYINT(1) DEFAULT 0 COMMENT 'Приоритет запроса. От 0 до 2',
            `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время отправки записи',
            `status` ENUM ('pending', 'processing', 'success', 'failed') DEFAULT 'pending' COMMENT 'Статус обработки запроса',
            `error` VARCHAR(255) DEFAULT NULL COMMENT 'Текст ошибки обработки запроса',
            `code` SMALLINT DEFAULT NULL COMMENT 'Код ошибки обработки запроса',
            `response` JSON DEFAULT NULL COMMENT 'Ответ от Telegram Bot API',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания записи',
            `processed_at` TIMESTAMP DEFAULT NULL COMMENT 'Дата обработки запроса',
            CHECK (JSON_VALID(data))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_telegram_messages_user_id` ON `telegram_messages` (`user_id`);");
        $this->execute("CREATE INDEX `idx_telegram_messages_method` ON `telegram_messages` (`method`);");
        $this->execute("CREATE INDEX `idx_telegram_messages_type` ON `telegram_messages` (`type`);");
        $this->execute("CREATE INDEX `idx_telegram_messages_priority` ON `telegram_messages` (`priority`);");
        $this->execute("CREATE INDEX `idx_telegram_messages_status` ON `telegram_messages` (`status`);");
    }
}
