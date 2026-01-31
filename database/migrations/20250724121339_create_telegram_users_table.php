<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTelegramUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `telegram_users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный идентификатор пользователя',
            `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Идентификатор пользователя в Телеграм (chat_id)',
            `username` VARCHAR(32) COMMENT 'Имя пользователя/логин пользователя Телеграм',
            `first_name` VARCHAR(64) COMMENT 'Имя',
            `last_name` VARCHAR(64) COMMENT 'Фамилия',
            `language_code` VARCHAR(10) COMMENT 'Код языка пользователя',
            `is_premium` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Премиум пользователь',
            `is_user_banned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Пользователь заблокирован',
            `is_bot_banned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Бот заблокирован пользователем',
            `is_subscribed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Подписан на канал(ы)',
            `referral_code` VARCHAR(32) COMMENT 'Реферальный код',
            `invited_user_id` BIGINT UNSIGNED COMMENT 'Идентификатор приглашенного пользователя',
            `utm` VARCHAR(255) COMMENT 'UTM метки',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата обновления'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE UNIQUE INDEX `idx_telegram_users_user_id` ON `telegram_users` (`user_id`);");
        $this->execute("CREATE INDEX `idx_telegram_users_is_user_banned` ON `telegram_users` (`is_user_banned`);");
    }
}
