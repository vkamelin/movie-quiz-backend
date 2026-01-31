<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokensTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `refresh_tokens` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Token identifier',
            `user_id` INT UNSIGNED NOT NULL COMMENT 'User ID',
            `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of token',
            `jti` VARCHAR(64) NOT NULL COMMENT 'JWT ID',
            `expires_at` INT UNSIGNED NOT NULL COMMENT 'Expiry timestamp',
            `revoked` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Revoked flag',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation time',
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Update time'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        $this->execute("CREATE INDEX `idx_refresh_tokens_user` ON `refresh_tokens` (`user_id`);");
        $this->execute("CREATE UNIQUE INDEX `idx_refresh_tokens_hash` ON `refresh_tokens` (`token_hash`);");
        $this->execute("CREATE INDEX `idx_refresh_tokens_jti` ON `refresh_tokens` (`jti`);");
        $this->execute("CREATE INDEX `idx_refresh_tokens_expires` ON `refresh_tokens` (`expires_at`);");
    }
}
