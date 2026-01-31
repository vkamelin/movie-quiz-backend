<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLeaderboardMonthlyTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `leaderboard_monthly` (
            `month` VARCHAR(7) NOT NULL, -- Формат: YYYY-MM (например, 2026-02)
            `user_id` BIGINT NOT NULL PRIMARY KEY,
            `target` ENUM('tg', 'vk') NOT NULL,
            `score` INT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`month`, `user_id`, `target`)
        );");

        $this->execute("CREATE INDEX `idx_leaderboard_monthly_score` ON `leaderboard_monthly` (`score` DESC);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `leaderboard_monthly`;");
    }
}
