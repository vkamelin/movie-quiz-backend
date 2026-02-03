<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesEmojiTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `movies_emoji` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `emoji` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE INDEX `idx_movies_emoji_movie_id` ON `movies_emoji` (`movie_id`)");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `movies_emoji`;");
    }
}
