<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateQuizOptionsTable extends AbstractMigration
{
    public function ip(): void
    {
        $this->execute("CREATE TABLE IF NOT EXISTS `quiz_options` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `movie_option_id` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");
    }
}
