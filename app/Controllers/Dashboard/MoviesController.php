<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Helpers\Flash;
use App\Helpers\Response;
use App\Helpers\View;
use PDO;
use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Контроллер управления фильмами.
 */
final class MoviesController
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Отображает список фильмов в виде карточек.
     */
    public function index(Req $req, Res $res): Res
    {
        $data = [
            'title' => 'Фильмы',
        ];

        return View::render($res, 'dashboard/movies/index.php', $data, 'layouts/main.php');
    }

    /**
     * Возвращает данные фильмов для карточек.
     */
    public function data(Req $req, Res $res): Res
    {
        $p = (array)$req->getParsedBody();
        $start = max(0, (int)($p['start'] ?? 0));
        $length = (int)($p['length'] ?? 100);
        $draw = (int)($p['draw'] ?? 0);
        if ($length === -1) {
            $start = 0;
        }

        $params = [];
        $searchValue = $p['search']['value'] ?? '';
        $whereSql = '';
        if ($searchValue !== '') {
            $whereSql = 'WHERE m.title LIKE :search1 OR m.title_original LIKE :search2';
            $params['search1'] = '%' . $searchValue . '%';
            $params['search2'] = '%' . $searchValue . '%';
        }

        $sql = "SELECT 
                    m.id,
                    m.title,
                    m.title_original,
                    m.poster_url,
                    m.poster_url_preview,
                    m.rating_kinopoisk,
                    m.rating_imdb,
                    m.year,
                    m.status,
                    m.description,
                    GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as countries,
                    GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
                FROM movies m
                LEFT JOIN movies_countries mc ON m.id = mc.movie_id
                LEFT JOIN countries c ON mc.country_id = c.id
                LEFT JOIN movies_genres mg ON m.id = mg.movie_id
                LEFT JOIN genres g ON mg.genre_id = g.id
                {$whereSql}
                GROUP BY m.id
                ORDER BY m.status DESC, m.rating_kinopoisk DESC";
        if ($length > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        if ($length > 0) {
            $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $countSql = "SELECT COUNT(DISTINCT m.id) 
                     FROM movies m
                     LEFT JOIN movies_countries mc ON m.id = mc.movie_id
                     LEFT JOIN countries c ON mc.country_id = c.id
                     LEFT JOIN movies_genres mg ON m.id = mg.movie_id
                     LEFT JOIN genres g ON mg.genre_id = g.id
                     {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue(':' . $key, $val);
        }
        $countStmt->execute();
        $recordsFiltered = (int)$countStmt->fetchColumn();

        $recordsTotal = (int)$this->db->query('SELECT COUNT(*) FROM movies')->fetchColumn();

        return Response::json($res, 200, [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Отображает детальную информацию о фильме.
     */
    public function view(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        
        // Получаем основную информацию о фильме
        $stmt = $this->db->prepare('SELECT * FROM movies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $movie = $stmt->fetch();
        
        if (!$movie) {
            return $res->withStatus(404);
        }

        // Получаем жанры
        $genresStmt = $this->db->prepare('
            SELECT g.name FROM genres g 
            INNER JOIN movies_genres mg ON g.id = mg.genre_id 
            WHERE mg.movie_id = :movie_id 
            ORDER BY g.name
        ');
        $genresStmt->execute(['movie_id' => $id]);
        $genres = $genresStmt->fetchAll(PDO::FETCH_COLUMN);

        // Получаем страны
        $countriesStmt = $this->db->prepare('
            SELECT c.name FROM countries c 
            INNER JOIN movies_countries mc ON c.id = mc.country_id 
            WHERE mc.movie_id = :movie_id 
            ORDER BY c.name
        ');
        $countriesStmt->execute(['movie_id' => $id]);
        $countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);

        $data = [
            'title' => 'Просмотр фильма',
            'movie' => $movie,
            'genres' => $genres,
            'countries' => $countries,
        ];

        return View::render($res, 'dashboard/movies/view.php', $data, 'layouts/main.php');
    }

    /**
     * Отображает форму редактирования статуса фильма.
     */
    public function edit(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        $stmt = $this->db->prepare('SELECT id, title, title_original, year, status FROM movies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $movie = $stmt->fetch();
        
        if (!$movie) {
            return $res->withStatus(404);
        }

        // Получаем жанры
        $genresStmt = $this->db->prepare('
            SELECT g.name FROM genres g 
            INNER JOIN movies_genres mg ON g.id = mg.genre_id 
            WHERE mg.movie_id = :movie_id 
            ORDER BY g.name
        ');
        $genresStmt->execute(['movie_id' => $id]);
        $genres = $genresStmt->fetchAll(PDO::FETCH_COLUMN);

        // Получаем страны
        $countriesStmt = $this->db->prepare('
            SELECT c.name FROM countries c 
            INNER JOIN movies_countries mc ON c.id = mc.country_id 
            WHERE mc.movie_id = :movie_id 
            ORDER BY c.name
        ');
        $countriesStmt->execute(['movie_id' => $id]);
        $countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);

        $data = [
            'title' => 'Редактирование фильма',
            'movie' => $movie,
            'errors' => [],
            'genres' => $genres,
            'countries' => $countries,
        ];

        return View::render($res, 'dashboard/movies/edit.php', $data, 'layouts/main.php');
    }

    /**
     * Обновляет статус фильма.
     */
    public function update(Req $req, Res $res, array $args): Res
    {
        $id = (int)($args['id'] ?? 0);
        
        // Проверяем существование фильма
        $stmt = $this->db->prepare('SELECT id FROM movies WHERE id = :id');
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            return $res->withStatus(404);
        }

        $data = (array)$req->getParsedBody();
        $status = (int)($data['status'] ?? 1);

        // Валидация статуса
        if (!in_array($status, [0, 1])) {
            $errors = ['Некорректный статус'];
            $movieStmt = $this->db->prepare('SELECT id, title, title_original, year, status FROM movies WHERE id = :id');
            $movieStmt->execute(['id' => $id]);
            $movie = $movieStmt->fetch();
            
            $params = [
                'title' => 'Редактирование фильма',
                'movie' => $movie,
                'errors' => $errors,
            ];
            return View::render($res, 'dashboard/movies/edit.php', $params, 'layouts/main.php');
        }

        // Обновляем статус
        $updateStmt = $this->db->prepare('UPDATE movies SET status = :status WHERE id = :id');
        $updateStmt->execute([
            'status' => $status,
            'id' => $id,
        ]);

        Flash::add('success', 'Статус фильма обновлен');
        return $res->withHeader('Location', '/dashboard/movies')->withStatus(302);
    }

    /**
     * Поиск фильма по ID и определение его позиции в общем списке.
     */
    public function searchById(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $movieId = (int)($data['movie_id'] ?? 0);

        if ($movieId <= 0) {
            return Response::json($res, 400, [
                'success' => false,
                'found' => false,
                'message' => 'Некорректный ID фильма',
            ]);
        }

        // Проверяем существование фильма
        $stmt = $this->db->prepare('SELECT COUNT(*) as position FROM movies WHERE id <= :movie_id ORDER BY status DESC, created_at DESC');
        $stmt->execute(['movie_id' => $movieId]);
        $position = (int)$stmt->fetchColumn();

        $existsStmt = $this->db->prepare('SELECT id FROM movies WHERE id = :movie_id');
        $existsStmt->execute(['movie_id' => $movieId]);

        if (!$existsStmt->fetch()) {
            return Response::json($res, 404, [
                'success' => false,
                'found' => false,
                'message' => 'Фильм не найден',
            ]);
        }

        return Response::json($res, 200, [
            'success' => true,
            'found' => true,
            'position' => $position,
            'message' => 'Фильм найден',
        ]);
    }

    /**
     * Массовое обновление статуса фильмов.
     */
    public function bulkStatus(Req $req, Res $res): Res
    {
        $data = (array)$req->getParsedBody();
        $action = $data['action'] ?? '';
        $movieIds = json_decode($data['movie_ids'] ?? '[]', true);

        if (empty($movieIds) || !is_array($movieIds)) {
            return Response::json($res, 400, [
                'success' => false,
                'message' => 'Не указаны ID фильмов для обновления',
            ]);
        }

        if (!in_array($action, ['activate', 'deactivate', 'toggle'])) {
            return Response::json($res, 400, [
                'success' => false,
                'message' => 'Некорректное действие',
            ]);
        }

        // Проверяем существование фильмов
        $placeholders = str_repeat('?,', count($movieIds) - 1) . '?';
        $checkSql = "SELECT id, status FROM movies WHERE id IN ($placeholders)";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute(array_values($movieIds));
        $movies = $checkStmt->fetchAll();

        if (count($movies) !== count($movieIds)) {
            return Response::json($res, 400, [
                'success' => false,
                'message' => 'Некоторые фильмы не найдены',
            ]);
        }

        $processed = 0;
        $this->db->beginTransaction();

        try {
            foreach ($movies as $movie) {
                $newStatus = $movie['status'];
                
                switch ($action) {
                    case 'activate':
                        $newStatus = 1;
                        break;
                    case 'deactivate':
                        $newStatus = 0;
                        break;
                    case 'toggle':
                        $newStatus = $movie['status'] ? 0 : 1;
                        break;
                }

                if ($newStatus !== $movie['status']) {
                    $updateStmt = $this->db->prepare('UPDATE movies SET status = :status WHERE id = :id');
                    $updateStmt->execute([
                        'status' => $newStatus,
                        'id' => $movie['id']
                    ]);
                    $processed++;
                }
            }

            $this->db->commit();

            return Response::json($res, 200, [
                'success' => true,
                'message' => "Обработано фильмов: $processed",
                'processed' => $processed,
            ]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            
            return Response::json($res, 500, [
                'success' => false,
                'message' => 'Ошибка при обновлении статуса фильмов',
            ]);
        }
    }
}