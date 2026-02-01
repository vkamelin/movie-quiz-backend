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
 * Команда для установки статуса 0 для фильмов определенной страны.
 */
class SetMovieStatusByCountryCommand extends Command
{
    public string $signature = 'movies:set-status-by-country';
    public string $description = 'Set status 0 for movies of specified country';

    public function handle(array $arguments, Kernel $kernel): int
    {
        if (count($arguments) === 0) {
            echo "Error: Country name is required" . PHP_EOL;
            echo "Usage: movies:set-status-by-country <country_name>" . PHP_EOL;
            return 1;
        }

        $countryName = trim($arguments[0]);
        
        if (empty($countryName)) {
            echo "Error: Country name cannot be empty" . PHP_EOL;
            return 1;
        }

        try {
            $db = Database::getInstance();

            // Проверяем, существует ли страна
            $countryQuery = $db->prepare('SELECT id FROM countries WHERE name = :name');
            $countryQuery->execute(['name' => $countryName]);
            $country = $countryQuery->fetch(PDO::FETCH_ASSOC);

            if (!$country) {
                echo "Error: Country '{$countryName}' not found" . PHP_EOL;
                return 1;
            }

            $countryId = $country['id'];

            // Находим все фильмы данной страны
            $moviesQuery = $db->prepare('
                SELECT DISTINCT m.id, m.title, m.status 
                FROM movies m 
                INNER JOIN movies_countries mc ON m.id = mc.movie_id 
                WHERE mc.country_id = :country_id
            ');
            $moviesQuery->execute(['country_id' => $countryId]);
            $movies = $moviesQuery->fetchAll(PDO::FETCH_ASSOC);

            if (empty($movies)) {
                echo "No movies found for country '{$countryName}'" . PHP_EOL;
                return 0;
            }

            // Фильтруем только активные фильмы (статус = 1)
            $activeMovies = array_filter($movies, fn($movie) => $movie['status'] == 1);

            if (empty($activeMovies)) {
                echo "No active movies found for country '{$countryName}'" . PHP_EOL;
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
                
                echo "Successfully updated {$updatedCount} movies of country '{$countryName}' to status 0" . PHP_EOL;
                
                Logger::info("Set status 0 for {$updatedCount} movies of country '{$countryName}'");
                
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