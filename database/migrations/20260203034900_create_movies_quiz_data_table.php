<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesQuizDataTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE `movies_quiz_data` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `emoji` JSON NOT NULL,
            `variants` JSON NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE UNIQUE INDEX `idx_movies_quiz_data_movie_id` ON `movies_quiz_data` (`movie_id`)");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE `movies_quiz_data`;");
    }
}
