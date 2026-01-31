<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramUpdatesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_updates` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор записи',
            `update_id` BIGINT UNIQUE NOT NULL COMMENT 'Уникальный идентификатор обновления из Telegram',
            `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Уникальный идентификатор пользователя в Telegram',
            `message_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Уникальный идентификатор сообщения в Telegram',
            `type` VARCHAR(50) NOT NULL COMMENT 'Тип обновления (сообщение, фото, документ, и т.д.)',
            `data` JSON NOT NULL COMMENT 'Данные обновления в формате JSON',
            `sent_at` TIMESTAMP DEFAULT NULL COMMENT 'Дата и время отправки сообщения/запроса пользователем в Telegram',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время создания записи',
            CHECK (JSON_VALID(data))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_telegram_updates_update_id` ON `telegram_updates` (`update_id`);");
        $this->execute("CREATE INDEX `idx_telegram_updates_user_id` ON `telegram_updates` (`user_id`);");
        $this->execute("CREATE INDEX `idx_telegram_updates_message_id` ON `telegram_updates` (`message_id`);");
        $this->execute("CREATE INDEX `idx_telegram_updates_type` ON `telegram_updates` (`type`);");
    }
}
