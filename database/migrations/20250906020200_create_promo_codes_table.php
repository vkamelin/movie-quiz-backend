<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePromoCodesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `promo_codes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'ID промокода',
            `batch_id` INT UNSIGNED NOT NULL COMMENT 'ID батча',
            `code` VARCHAR(255) NOT NULL COMMENT 'Промокод',
            `status` ENUM('available','issued','expired') NOT NULL DEFAULT 'available' COMMENT 'Статус',
            `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Срок действия',
            `issued_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Когда был выдан'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE UNIQUE INDEX `idx_promo_codes_code_unique` ON `promo_codes` (`code`);");
        $this->execute("CREATE INDEX `idx_promo_codes_status` ON `promo_codes` (`status`);");
        $this->execute("CREATE INDEX `idx_promo_codes_batch_id` ON `promo_codes` (`batch_id`);
        ");
    }
}

