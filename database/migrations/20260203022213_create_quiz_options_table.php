<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateQuizOptionsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `quiz_options` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `movie_option_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");

        $this->execute("CREATE INDEX `idx_quiz_options_movie_id` ON `quiz_options` (`movie_id`);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `quiz_options`;");
    }
}
