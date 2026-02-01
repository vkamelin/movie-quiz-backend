<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Kernel;
use App\Helpers\Database;
use App\Helpers\Logger;
use PDO;
use PDOException;

/**
 * Команда для установки статуса 0 для фильмов определенного жанра.
 */
class SetMovieStatusCommand extends Command
{
    public string $signature = 'movies:set-status';
    public string $description = 'Set status 0 for movies of specified genre';

    public function handle(array $arguments, Kernel $kernel): int
    {
        if (count($arguments) === 0) {
            echo "Error: Genre name is required" . PHP_EOL;
            echo "Usage: movies:set-status <genre_name>" . PHP_EOL;
            return 1;
        }

        $genreName = trim($arguments[0]);
        
        if (empty($genreName)) {
            echo "Error: Genre name cannot be empty" . PHP_EOL;
            return 1;
        }

        try {
            $db = Database::getInstance();

            // Проверяем, существует ли жанр
            $genreQuery = $db->prepare('SELECT id FROM genres WHERE name = :name');
            $genreQuery->execute(['name' => $genreName]);
            $genre = $genreQuery->fetch(PDO::FETCH_ASSOC);

            if (!$genre) {
                echo "Error: Genre '{$genreName}' not found" . PHP_EOL;
                return 1;
            }

            $genreId = $genre['id'];

            // Находим все фильмы данного жанра
            $moviesQuery = $db->prepare('
                SELECT DISTINCT m.id, m.title, m.status 
                FROM movies m 
                INNER JOIN movies_genres mg ON m.id = mg.movie_id 
                WHERE mg.genre_id = :genre_id
            ');
            $moviesQuery->execute(['genre_id' => $genreId]);
            $movies = $moviesQuery->fetchAll(PDO::FETCH_ASSOC);

            if (empty($movies)) {
                echo "No movies found for genre '{$genreName}'" . PHP_EOL;
                return 0;
            }

            // Фильтруем только активные фильмы (статус = 1)
            $activeMovies = array_filter($movies, fn($movie) => $movie['status'] == 1);

            if (empty($activeMovies)) {
                echo "No active movies found for genre '{$genreName}'" . PHP_EOL;
                return 0;
            }

            // Обновляем статус фильмов
            $updateQuery = $db->prepare('UPDATE movies SET status = 0 WHERE id = :id');
            $updatedCount = 0;

            $db->beginTransaction();

            try {
                foreach ($activeMovies as $movie) {
                    $updateQuery->execute(['id' => $movie['id']]);
                    $updatedCount++;
                }

                $db->commit();
                
                echo "Successfully updated {$updatedCount} movies of genre '{$genreName}' to status 0" . PHP_EOL;
                
                Logger::info("Set status 0 for {$updatedCount} movies of genre '{$genreName}'");
                
                return 0;
                
            } catch (PDOException $e) {
                $db->rollBack();
                Logger::error("Failed to update movie status: " . $e->getMessage());
                echo "Error: Failed to update movie status" . PHP_EOL;
                return 1;
            }

        } catch (PDOException $e) {
            Logger::error("Database error: " . $e->getMessage());
            echo "Error: Database error occurred" . PHP_EOL;
            return 1;
        } catch (\Exception $e) {
            Logger::error("Unexpected error: " . $e->getMessage());
            echo "Error: Unexpected error occurred" . PHP_EOL;
            return 1;
        }
    }
}