<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMoviesCountriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("CREATE TABLE movies_countries (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `movie_id` INT UNSIGNED NOT NULL,
            `country_id` INT UNSIGNED NOT NULL
        );");

        // indexes
        $this->execute("CREATE INDEX idx_movies_countries_movie_id ON countries (movie_id);");
        $this->execute("CREATE INDEX idx_movies_countries_country_id ON countries (country_id);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE movies_countries;");
    }
}
