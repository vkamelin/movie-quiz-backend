<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateReferralsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `referrals` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `inviter_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Telegram ID пригласившего',
            `invitee_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Telegram ID приглашённого',
            `via_code` VARCHAR(64) DEFAULT NULL COMMENT 'Реферальный код',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        // Уникальный индекс на приглашённого, чтобы не было дублей связывания
        $this->execute("CREATE UNIQUE INDEX `uidx_referrals_invitee` ON `referrals` (`invitee_user_id`);
        ");

        // Индекс на пригласившего для быстрых отчётов
        $this->execute("CREATE INDEX `idx_referrals_inviter` ON `referrals` (`inviter_user_id`);");
    }
}

