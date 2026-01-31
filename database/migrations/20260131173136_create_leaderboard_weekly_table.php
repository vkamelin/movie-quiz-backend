<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLeaderboardWeeklyTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `leaderboard_weekly` (
            `week` VARCHAR(10) NOT NULL, -- Формат: YYYY-W## (например, 2026-W05)
            `user_id` BIGINT NOT NULL,
            `target` ENUM('tg', 'vk') NOT NULL,
            `score` INT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`week`, `user_id`, `target`)
        );");

        $this->execute("CREATE INDEX `idx_leaderboard_weekly_score` ON `leaderboard_weekly` (`score` DESC);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `leaderboard_weekly`;");
    }
}
