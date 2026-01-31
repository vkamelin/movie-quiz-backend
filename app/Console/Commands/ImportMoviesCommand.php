<?php

namespace App\Console\Commands;

use App\Helpers\Logger;
use PDO;
use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\KinopoiskApi;
use PDOException;

class ImportMoviesCommand extends Command
{
    public string $signature = 'movies:import';
    public string $description = 'Import movies from database';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $page = 1;

        if (isset($arguments['page'])) {
            $page = (int)$arguments['page'];
        }

        $db = Database::getInstance();

        $genres = $db->query('SELECT id, name FROM genres')->fetchAll(PDO::FETCH_KEY_PAIR);
        $countries = $db->query('SELECT id, name FROM countries')->fetchAll(PDO::FETCH_KEY_PAIR);

        $api = new KinopoiskApi($_ENV['MOVIES_API_KEY']);

        $params = [
            'order' => 'RATING',
            'type' => 'FILM',
            'ratingFrom' => 6,
            'ratingTo' => 10,
            'yearFrom' => 1000,
            'yearTo' => 3000,
            'page' => $page,
        ];

        $moviesResult = $api->searchFilms($params);

        if ($moviesResult) {
            if ($moviesResult['total'] > 0) {
                foreach ($moviesResult['items'] as $movie) {
                    $movieGenres = [];
                    $movieCountries = [];

                    // genres
                    if (!empty($movie['genres'])) {
                        foreach ($movie['genres'] as $genre) {
                            if (!in_array($genre['genre'], $genres)) {
                                $genreId = $this->addGenre($genre['genre']);
                                $genres[$genreId] = $genre['genre'];

                                $movieGenres[] = $genreId;
                            } else {
                                $movieGenres[] = array_search($genre['genre'], $genres);
                            }
                        }
                    }

                    // countries
                    if (!empty($movie['countries'])) {
                        foreach ($movie['countries'] as $country) {
                            if (!in_array($country['country'], $countries)) {
                                $countryId = $this->addCountry($country['country']);
                                $countries[$countryId] = $country['country'];

                                $movieCountries[] = $countryId;
                            } else {
                                $movieCountries[] = array_search($country['country'], $countries);
                            }
                        }
                    }

                    $db->beginTransaction();

                    try {
                        $movieQuery = $db->prepare("INSERT INTO `movies` (kinopoisk_id, imdb_id, title, title_original, poster_url, poster_url_preview, rating_kinopoisk, rating_imdb, year) VALUES (:kinopoisk_id, :imdb_id, :title, :title_original, :poster_url, :poster_url_preview, :rating_kinopoisk, :rating_imdb, :year)");

                        $movieQuery->execute([
                            'kinopoisk_id' => $movie['kinopoiskId'],
                            'imdb_id' => $movie['imdbId'],
                            'title' => $movie['nameRu'],
                            'title_original' => $movie['nameOriginal'],
                            'poster_url' => $movie['posterUrl'],
                            'poster_url_preview' => $movie['posterUrlPreview'],
                            'rating_kinopoisk' => $movie['ratingKinopoisk'],
                            'rating_imdb' => $movie['ratingImdb'],
                            'year' => $movie['year'],
                        ]);
                    } catch (PDOException $e) {
                        $db->rollBack();
                        Logger::error($e->getMessage());
                        continue;
                    }
                }
            }

            if ($filmsResult['totalPages'] == 0) {
                return 0;
            }
        }

        return 0;
    }

    private function addGenre(string $name): int|null
    {
        $db = Database::getInstance();

        try {
            $query = $db->prepare('INSERT INTO genres (name) VALUES (:name)');
            $query->execute(['name' => $name]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            Logger::error($e->getMessage());
            return null;
        }
    }

    private function addCountry(string $name): int|null
    {
        $db = Database::getInstance();

        try {
            $query = $db->prepare('INSERT INTO countries (name) VALUES (:name)');
            $query->execute(['name' => $name]);

            return $db->lastInsertId();
        } catch (PDOException $e) {
            Logger::error($e->getMessage());
            return null;
        }
    }
}