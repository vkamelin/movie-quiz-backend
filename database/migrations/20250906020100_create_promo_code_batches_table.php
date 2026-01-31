<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePromoCodeBatchesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `promo_code_batches` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'ID батча',
            `filename` VARCHAR(255) NOT NULL COMMENT 'Имя исходного файла',
            `created_by` INT UNSIGNED DEFAULT NULL COMMENT 'ID пользователя панели, создавшего батч',
            `total` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Сколько кодов загружено',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE = utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_pcb_created_by` ON `promo_code_batches` (`created_by`);");
    }
}

