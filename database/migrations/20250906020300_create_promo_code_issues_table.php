<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePromoCodeIssuesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `promo_code_issues` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'ID выдачи',
            `code_id` INT UNSIGNED NOT NULL COMMENT 'ID промокода',
            `telegram_user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Telegram user_id из telegram_users',
            `issued_by` INT UNSIGNED DEFAULT NULL COMMENT 'ID пользователя панели, выдавшего промокод',
            `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Когда выдан'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_pci_code_id` ON `promo_code_issues` (`code_id`);");
        $this->execute("CREATE INDEX `idx_pci_telegram_user_id` ON `promo_code_issues` (`telegram_user_id`);");
    }
}

