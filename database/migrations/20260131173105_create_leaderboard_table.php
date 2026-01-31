<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLeaderboardTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `leaderboard` (
            `user_id` BIGINT NOT NULL PRIMARY KEY,
            `target` ENUM('tg', 'vk') NOT NULL,
            `score` INT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE UNIQUE INDEX `idx_leaderboard_user_id_target` ON `leaderboard` (`user_id`, `target`);");
        $this->execute("CREATE INDEX `idx_leaderboard_score` ON `leaderboard` (`score` DESC);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `leaderboard`;");
    }
}
