<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesGenresTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE movies_genres (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `genre_id` INT UNSIGNED NOT NULL
        );");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE movies_genres;");
    }
}
