<?php

namespace App\Console\Commands;

use App\Helpers\Logger;
use PDO;
use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\PoiskKinoApi;
use PDOException;
use Throwable;

class ImportMoviesCommand extends Command
{
    public string $signature = 'movies:import';
    public string $description = 'Import movies from database';

    public function handle(array $arguments, Kernel $kernel): int
    {
        $page = 1;
        $limit = 250;
        $maxPages = 1;

        if (count($arguments) > 0) {
            foreach ($arguments as $argument) {
                $argumentArray = explode('=', $argument);

                if ($argumentArray[0] == 'page') {
                    $page = (int)$argumentArray[1];
                }

                if ($argumentArray[0] == 'limit') {
                    $limit = (int)$argumentArray[1];
                }

                if ($argumentArray[0] == 'maxPages') {
                    $maxPages = (int)$argumentArray[1];
                }
            }
        }

        $db = Database::getInstance();

        $genres = $db->query('SELECT id, name FROM genres')->fetchAll(PDO::FETCH_KEY_PAIR);
        $countries = $db->query('SELECT id, name FROM countries')->fetchAll(PDO::FETCH_KEY_PAIR);

        $api = new PoiskKinoApi($_ENV['MOVIES_API_KEY']);

        for ($i = 0; $i < $maxPages; $i++) {
            $currentPage = $page + $i;

            try {
                $moviesResult = $api->movies()
                    ->withType('movie')
                    ->withPage($currentPage)
                    ->withLimit($limit)
                    ->withNotNullFields('rating.kp')
                    ->withSortFields('rating.kp')
                    ->withSortTypes('-1')
                    ->execute();
            } catch (Throwable $e) {
                Logger::error($e->getMessage());
                echo $e->getMessage() . PHP_EOL;
                echo $e->getTraceAsString() . PHP_EOL;
                return 0;
            }

            if ($moviesResult) {
                if ($moviesResult['total'] > 0) {
                    foreach ($moviesResult['docs'] as $movie) {
                        $movieGenres = [];
                        $movieCountries = [];

                        // genres
                        if (!empty($movie['genres'])) {
                            $skipMovie = false;

                            foreach ($movie['genres'] as $genre) {
                                // skip movies with genre "документальный"
                                if ($genre['name'] === 'документальный') {
                                    $skipMovie = true;
                                    break;
                                }

                                if (!in_array($genre['name'], $genres)) {
                                    $genreId = $this->addGenre($genre['name']);
                                    $genres[$genreId] = $genre['name'];

                                    $movieGenres[] = $genreId;
                                } else {
                                    $movieGenres[] = array_search($genre['name'], $genres);
                                }
                            }

                            if ($skipMovie) {
                                continue;
                            }
                        }

                        // countries
                        if (!empty($movie['countries'])) {
                            foreach ($movie['countries'] as $country) {
                                if (!in_array($country['name'], $countries)) {
                                    $countryId = $this->addCountry($country['name']);
                                    $countries[$countryId] = $country['name'];

                                    $movieCountries[] = $countryId;
                                } else {
                                    $movieCountries[] = array_search($country['name'], $countries);
                                }
                            }
                        }

                        if (empty($movie['name'])) {
                            // skip movies without title
                            continue;
                        }

                        $db->beginTransaction();

                        try {
                            $movieQuery = $db->prepare("INSERT INTO `movies` (`kinopoisk_id`, `title`, `title_original`, `poster_url`, `poster_url_preview`, `rating_kinopoisk`, `rating_imdb`, `year`, `description`) VALUES (:kinopoisk_id, :title, :title_original, :poster_url, :poster_url_preview, :rating_kinopoisk, :rating_imdb, :year, :description)");

                            $movieQuery->execute([
                                'kinopoisk_id' => $movie['id'],
                                'title' => $movie['name'],
                                'title_original' => $movie['alternativeName'],
                                'poster_url' => !empty($movie['poster']['url']) ? $movie['poster']['url'] : '',
                                'poster_url_preview' => !empty($movie['poster']['previewUrl']) ? $movie['poster']['previewUrl'] : null,
                                'rating_kinopoisk' => !empty($movie['rating']['kp']) ? $movie['rating']['kp'] : null,
                                'rating_imdb' => !empty($movie['rating']['imdb_id']) ? $movie['rating']['imdb_id'] : null,
                                'year' => $movie['year'] ?? null,
                                'description' => !empty($movie['description']) ? $movie['description'] : '',
                            ]);

                            $movieId = $db->lastInsertId();

                            if (!empty($movieGenres)) {
                                foreach ($movieGenres as $genreId) {
                                    $genreQuery = $db->prepare('INSERT INTO movies_genres (movie_id, genre_id) VALUES (:movie_id, :genre_id)');
                                    $genreQuery->execute([
                                        'movie_id' => $movieId,
                                        'genre_id' => $genreId
                                    ]);
                                }
                            }

                            if (!empty($movieCountries)) {
                                foreach ($movieCountries as $countryId) {
                                    $countryQuery = $db->prepare('INSERT INTO movies_countries (movie_id, country_id) VALUES (:movie_id, :country_id)');
                                    $countryQuery->execute([
                                        'movie_id' => $movieId,
                                        'country_id' => $countryId
                                    ]);
                                }
                            }

                            $db->commit();
                        } catch (PDOException $e) {
                            $db->rollBack();
                            Logger::error($e->getMessage());
                            continue;
                        }
                    }
                }
            }

            echo "Страница {$currentPage} обработана" . PHP_EOL;
            sleep(5);
        }

        echo "Импорт завершен" . PHP_EOL;

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