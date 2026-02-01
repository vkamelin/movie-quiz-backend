<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `movies` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `kinopoisk_id` BIGINT DEFAULT NULL,
            `title` VARCHAR(255) NOT NULL,
            `title_original` VARCHAR(255) DEFAULT NULL,
            `poster_url` VARCHAR(255) DEFAULT NULL,
            `poster_url_preview` VARCHAR(255) DEFAULT NULL,
            `rating_kinopoisk` DOUBLE(4, 3) DEFAULT NULL,
            `rating_imdb` DOUBLE(4, 3) DEFAULT NULL,
            `year` SMALLINT(4) DEFAULT NULL,
            `description` TEXT NOT NULL,
            `status` TINYINT(1) DEFAULT '1',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE UNIQUE INDEX `idx_movies_kinopoisk_id` ON `movies` (`kinopoisk_id`);");
        $this->execute("CREATE INDEX `idx_movies_status` ON `movies` (`status`);");
    }

    public function down(): void
    {
        $this->execute("DROP INDEX `idx_movies_status` ON `movies`;");
        $this->execute("DROP TABLE `movies`;");
    }
}
