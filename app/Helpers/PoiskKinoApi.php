<?php

namespace App\Helpers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Класс для работы с API ПоискКино (poiskkino.dev)
 * 
 * Позволяет получать информацию о фильмах, сериалах, актерах и других данных
 * из базы данных КиноПоиск через REST API.
 * 
 * Пример использования:
 * 
 * $api = new PoiskKinoApi('your-api-key');
 * 
 * // Поиск фильмов
 * $movies = $api->movies()
 *     ->withYear(2023)
 *     ->withGenre('драма')
 *     ->withRating('8-10')
 *     ->execute();
 * 
 * // Поиск по ID
 * $movie = $api->movieById(666)->execute();
 * 
 * // Поиск по названию
 * $results = $api->searchMovies('Название фильма')->execute();
 */
class PoiskKinoApi
{
    private string $apiKey;
    private string $baseUrl = 'https://api.poiskkino.dev';
    private array $headers = [];
    private array $queryParams = [];
    private string $endpoint = '';
    private ?string $id = null;
    private string $method = 'GET';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->headers = [
            'X-API-KEY' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * Универсальный поиск фильмов и сериалов с фильтрами
     * 
     * @return static
     */
    public function movies(): static
    {
        $this->endpoint = '/v1.4/movie';
        return $this;
    }

    /**
     * Поиск фильма по ID
     * 
     * @param int $id ID фильма
     * @return static
     */
    public function movieById(int $id): static
    {
        $this->endpoint = '/v1.4/movie/' . $id;
        $this->method = 'GET';
        return $this;
    }

    /**
     * Поиск фильмов по названию
     * 
     * @param string $query Поисковый запрос
     * @return static
     */
    public function searchMovies(string $query): static
    {
        $this->endpoint = '/v1.4/movie/search';
        $this->withQuery($query);
        return $this;
    }

    /**
     * Получить случайный фильм из базы
     * 
     * @return static
     */
    public function randomMovie(): static
    {
        $this->endpoint = '/v1.4/movie/random';
        return $this;
    }

    /**
     * Универсальный поиск персон (актеров, режиссеров, и т.д.)
     * 
     * @return static
     */
    public function persons(): static
    {
        $this->endpoint = '/v1.4/person';
        return $this;
    }

    /**
     * Поиск персон по имени
     * 
     * @param string $query Поисковый запрос
     * @return static
     */
    public function searchPersons(string $query): static
    {
        $this->endpoint = '/v1.4/person/search';
        $this->withQuery($query);
        return $this;
    }

    /**
     * Поиск персоны по ID
     * 
     * @param int $id ID персоны
     * @return static
     */
    public function personById(int $id): static
    {
        $this->endpoint = '/v1.4/person/' . $id;
        return $this;
    }

    /**
     * Универсальный поиск с фильтрами - отзывы
     * 
     * @return static
     */
    public function reviews(): static
    {
        $this->endpoint = '/v1.4/review';
        return $this;
    }

    /**
     * Поиск сезонов
     * 
     * @return static
     */
    public function seasons(): static
    {
        $this->endpoint = '/v1.4/season';
        return $this;
    }

    /**
     * Поиск студий
     * 
     * @return static
     */
    public function studios(): static
    {
        $this->endpoint = '/v1.4/studio';
        return $this;
    }

    /**
     * Поиск ключевых слов
     * 
     * @return static
     */
    public function keywords(): static
    {
        $this->endpoint = '/v1.4/keyword';
        return $this;
    }

    /**
     * Поиск картинок
     * 
     * @return static
     */
    public function images(): static
    {
        $this->endpoint = '/v1.4/image';
        return $this;
    }

    /**
     * Поиск коллекций
     * 
     * @return static
     */
    public function lists(): static
    {
        $this->endpoint = '/v1.4/list';
        return $this;
    }

    /**
     * Поиск коллекции по slug
     * 
     * @param string $slug Slug коллекции
     * @return static
     */
    public function listBySlug(string $slug): static
    {
        $this->endpoint = '/v1.4/list/' . $slug;
        return $this;
    }

    /**
     * Поиск коллекции по slug с фильмами (v1.5)
     * 
     * @param string $slug Slug коллекции
     * @return static
     */
    public function listWithMovies(string $slug): static
    {
        $this->endpoint = '/v1.5/list/' . $slug;
        return $this;
    }

    /**
     * Получить список стран, жанров, и т.д.
     * 
     * @param string $field Поле для получения значений
     * @return static
     */
    public function possibleValues(string $field): static
    {
        $this->endpoint = '/v1/movie/possible-values-by-field';
        $this->withField($field);
        return $this;
    }

    // Методы для установки параметров фильтрации

    /**
     * Установить номер страницы
     * 
     * @param int $page Номер страницы
     * @return static
     */
    public function withPage(int $page): static
    {
        $this->queryParams['page'] = $page;
        return $this;
    }

    /**
     * Установить количество элементов на странице
     * 
     * @param int $limit Количество элементов (1-250)
     * @return static
     */
    public function withLimit(int $limit): static
    {
        $this->queryParams['limit'] = $limit;
        return $this;
    }

    /**
     * Установить список полей для выбора
     * 
     * @param string|array $fields Поле или список полей
     * @return static
     */
    public function withSelectFields($fields): static
    {
        if (is_array($fields)) {
            $this->queryParams['selectFields'] = implode(',', $fields);
        } else {
            $this->queryParams['selectFields'] = $fields;
        }
        return $this;
    }

    /**
     * Установить поля, которые не должны быть null или пусты
     * 
     * @param string|array $fields Поле или список полей
     * @return static
     */
    public function withNotNullFields($fields): static
    {
        if (is_array($fields)) {
            $this->queryParams['notNullFields'] = implode(',', $fields);
        } else {
            $this->queryParams['notNullFields'] = $fields;
        }
        return $this;
    }

    /**
     * Установить поля для сортировки
     * 
     * @param string|array $fields Поле или список полей для сортировки
     * @return static
     */
    public function withSortFields($fields): static
    {
        if (is_array($fields)) {
            $this->queryParams['sortField'] = implode(',', $fields);
        } else {
            $this->queryParams['sortField'] = $fields;
        }
        return $this;
    }

    /**
     * Установить тип сортировки
     * 
     * @param string|array $types Тип сортировки ("1", "-1") или массив типов
     * @return static
     */
    public function withSortTypes($types): static
    {
        if (is_array($types)) {
            $this->queryParams['sortType'] = implode(',', $types);
        } else {
            $this->queryParams['sortType'] = $types;
        }
        return $this;
    }

    /**
     * Поиск по ID KinoPoisk
     * 
     * @param array|string $ids ID фильмов
     * @return static
     */
    public function withIds($ids): static
    {
        $this->addArrayParam('id', $ids);
        return $this;
    }

    /**
     * Поиск по году
     * 
     * @param array|string $years Годы (можно использовать диапазоны)
     * @return static
     */
    public function withYear($years): static
    {
        $this->addArrayParam('year', $years);
        return $this;
    }

    /**
     * Поиск по жанрам
     * 
     * @param array|string $genres Жанры (можно использовать + для включения и ! для исключения)
     * @return static
     */
    public function withGenre($genres): static
    {
        $this->addArrayParam('genres.name', $genres);
        return $this;
    }

    /**
     * Поиск по странам
     * 
     * @param array|string $countries Страны (можно использовать + для включения и ! для исключения)
     * @return static
     */
    public function withCountry($countries): static
    {
        $this->addArrayParam('countries.name', $countries);
        return $this;
    }

    /**
     * Поиск по рейтингу KinoPoisk
     * 
     * @param array|string $ratings Рейтинги (можно использовать диапазоны)
     * @return static
     */
    public function withRating($ratings): static
    {
        $this->addArrayParam('rating.kp', $ratings);
        return $this;
    }

    /**
     * Поиск по рейтингу IMDB
     * 
     * @param array|string $ratings Рейтинги IMDB
     * @return static
     */
    public function withRatingImdb($ratings): static
    {
        $this->addArrayParam('rating.imdb', $ratings);
        return $this;
    }

    /**
     * Поиск по типу фильма
     * 
     * @param string $type Тип фильма (movie, tv-series, cartoon, anime, animated-series)
     * @return static
     */
    public function withType(string $type): static
    {
        $this->queryParams['type'] = $type;
        return $this;
    }

    /**
     * Поиск по статусу фильма
     * 
     * @param array|string $statuses Статусы (announced, completed, filming, post-production, pre-production)
     * @return static
     */
    public function withStatus($statuses): static
    {
        $this->addArrayParam('status', $statuses);
        return $this;
    }

    /**
     * Поиск по индикатору сериала
     * 
     * @param bool $isSeries true для сериалов, false для фильмов
     * @return static
     */
    public function withIsSeries(bool $isSeries): static
    {
        $this->queryParams['isSeries'] = $isSeries ? 'true' : 'false';
        return $this;
    }

    /**
     * Поиск по продолжительности фильма (в минутах)
     * 
     * @param array|string $lengths Продолжительность (можно использовать диапазоны)
     * @return static
     */
    public function withMovieLength($lengths): static
    {
        $this->addArrayParam('movieLength', $lengths);
        return $this;
    }

    /**
     * Поиск по возрастному рейтингу
     * 
     * @param array|string $ageRatings Возрастные рейтинги
     * @return static
     */
    public function withAgeRating($ageRatings): static
    {
        $this->addArrayParam('ageRating', $ageRatings);
        return $this;
    }

    /**
     * Поиск по количеству голосов
     * 
     * @param array|string $votes Количество голосов (можно использовать диапазоны)
     * @return static
     */
    public function withVotes($votes): static
    {
        $this->addArrayParam('votes.kp', $votes);
        return $this;
    }

    /**
     * Поиск по бюджету
     * 
     * @param array|string $budgets Бюджет (можно использовать диапазоны)
     * @return static
     */
    public function withBudget($budgets): static
    {
        $this->addArrayParam('budget.value', $budgets);
        return $this;
    }

    /**
     * Поиск по сборам в мире
     * 
     * @param array|string $fees Сборы (можно использовать диапазоны)
     * @return static
     */
    public function withWorldFees($fees): static
    {
        $this->addArrayParam('fees.world.value', $fees);
        return $this;
    }

    /**
     * Поиск по дате премьеры в России
     * 
     * @param array|string $dates Даты премьеры
     * @return static
     */
    public function withPremiereRussia($dates): static
    {
        $this->addArrayParam('premiere.russia', $dates);
        return $this;
    }

    /**
     * Поиск по дате премьеры в мире
     * 
     * @param array|string $dates Даты премьеры
     * @return static
     */
    public function withPremiereWorld($dates): static
    {
        $this->addArrayParam('premiere.world', $dates);
        return $this;
    }

    /**
     * Поиск по коллекциям
     * 
     * @param array|string $lists Коллекции
     * @return static
     */
    public function withList($lists): static
    {
        $this->addArrayParam('lists', $lists);
        return $this;
    }

    /**
     * Поиск по доступным платформам для просмотра
     * 
     * @param array|string $platforms Платформы
     * @return static
     */
    public function withWatchability($platforms): static
    {
        $this->addArrayParam('watchability.items.name', $platforms);
        return $this;
    }

    // Методы для персон

    /**
     * Поиск по ID персон
     * 
     * @param array|string $ids ID персон
     * @return static
     */
    public function withPersonIds($ids): static
    {
        $this->addArrayParam('id', $ids);
        return $this;
    }

    /**
     * Поиск по полу
     * 
     * @param array|string $sex Пол (Женский, Мужской)
     * @return static
     */
    public function withSex($sex): static
    {
        $this->addArrayParam('sex', $sex);
        return $this;
    }

    /**
     * Поиск по росту
     * 
     * @param array|string $growth Рост (можно использовать диапазоны)
     * @return static
     */
    public function withGrowth($growth): static
    {
        $this->addArrayParam('growth', $growth);
        return $this;
    }

    /**
     * Поиск по дате рождения
     * 
     * @param array|string $birthdays Даты рождения
     * @return static
     */
    public function withBirthday($birthdays): static
    {
        $this->addArrayParam('birthday', $birthdays);
        return $this;
    }

    /**
     * Поиск по профессии
     * 
     * @param array|string $professions Профессии
     * @return static
     */
    public function withProfession($professions): static
    {
        $this->addArrayParam('profession.value', $professions);
        return $this;
    }

    // Методы для отзывов

    /**
     * Поиск по ID фильма для отзывов
     * 
     * @param array|string $movieIds ID фильмов
     * @return static
     */
    public function withMovieIds($movieIds): static
    {
        $this->addArrayParam('movieId', $movieIds);
        return $this;
    }

    /**
     * Поиск по типу отзыва
     * 
     * @param array|string $types Типы отзывов (Негативный, Нейтральный, Позитивный)
     * @return static
     */
    public function withReviewType($types): static
    {
        $this->addArrayParam('type', $types);
        return $this;
    }

    /**
     * Поиск по дате создания отзыва
     * 
     * @param array|string $dates Даты
     * @return static
     */
    public function withReviewDate($dates): static
    {
        $this->addArrayParam('date', $dates);
        return $this;
    }

    // Методы для изображений

    /**
     * Поиск по типу изображения
     * 
     * @param array|string $types Типы изображений
     * @return static
     */
    public function withImageType($types): static
    {
        $this->addArrayParam('type', $types);
        return $this;
    }

    /**
     * Поиск по языку изображения
     * 
     * @param array|string $languages Языки
     * @return static
     */
    public function withImageLanguage($languages): static
    {
        $this->addArrayParam('language', $languages);
        return $this;
    }

    /**
     * Поиск по высоте изображения
     * 
     * @param array|string $heights Высоты (можно использовать диапазоны)
     * @return static
     */
    public function withImageHeight($heights): static
    {
        $this->addArrayParam('height', $heights);
        return $this;
    }

    /**
     * Поиск по ширине изображения
     * 
     * @param array|string $widths Ширины (можно использовать диапазоны)
     * @return static
     */
    public function withImageWidth($widths): static
    {
        $this->addArrayParam('width', $widths);
        return $this;
    }

    // Методы для коллекций

    /**
     * Поиск по slug коллекции
     * 
     * @param array|string $slugs Slug коллекций
     * @return static
     */
    public function withSlug($slugs): static
    {
        $this->addArrayParam('slug', $slugs);
        return $this;
    }

    /**
     * Поиск по категории коллекции
     * 
     * @param array|string $categories Категории
     * @return static
     */
    public function withCategory($categories): static
    {
        $this->addArrayParam('category', $categories);
        return $this;
    }

    /**
     * Поиск по количеству фильмов в коллекции
     * 
     * @param array|string $counts Количество фильмов
     * @return static
     */
    public function withMoviesCount($counts): static
    {
        $this->addArrayParam('moviesCount', $counts);
        return $this;
    }

    // Вспомогательные методы

    /**
     * Установить поисковый запрос
     * 
     * @param string $query Поисковый запрос
     * @return static
     */
    public function withQuery(string $query): static
    {
        $this->queryParams['query'] = $query;
        return $this;
    }

    /**
     * Установить поле для получения возможных значений
     * 
     * @param string $field Поле
     * @return static
     */
    public function withField(string $field): static
    {
        $this->queryParams['field'] = $field;
        return $this;
    }

    /**
     * Добавить произвольный параметр
     * 
     * @param string $name Имя параметра
     * @param mixed $value Значение
     * @return static
     */
    public function withParam(string $name, $value): static
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * Добавить массивный параметр (поддерживает как массивы, так и одиночные значения)
     * 
     * @param string $name Имя параметра
     * @param array|string $value Значение
     * @return static
     */
    private function addArrayParam(string $name, $value): static
    {
        if (is_array($value)) {
            $this->queryParams[$name] = $value;
        } else {
            $this->queryParams[$name] = [$value];
        }
        return $this;
    }

    /**
     * Выполнить запрос к API
     * 
     * @return array Ответ API
     * @throws Exception|GuzzleException При ошибке запроса
     */
    public function execute(): array
    {
        if (empty($this->endpoint)) {
            throw new Exception('Не указан endpoint для запроса');
        }

        $client = new \GuzzleHttp\Client();
        
        $options = [
            'headers' => $this->headers,
            'query' => $this->queryParams,
            'timeout' => 30,
            'verify' => true
        ];

        try {
            $url = $this->baseUrl . $this->endpoint;
            $response = $client->request($this->method, $url, $options);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Ошибка декодирования JSON: ' . json_last_error_msg());
            }
            
            // Проверка на ошибки API
            if (isset($data['statusCode']) && $statusCode >= 400) {
                $errorMessage = $data['message'] ?? 'Неизвестная ошибка API';
                throw new Exception("API Error ({$data['statusCode']}): {$errorMessage}");
            }
            
            return $data;
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $message = "HTTP {$statusCode}: {$body}";
            }
            throw new Exception("Ошибка запроса к API: {$message}");
        } catch (Exception $e) {
            throw new Exception("Ошибка выполнения запроса: {$e->getMessage()}");
        }
    }

    /**
     * Сбросить параметры запроса
     * 
     * @return static
     */
    public function reset(): static
    {
        $this->queryParams = [];
        $this->endpoint = '';
        $this->method = 'GET';
        $this->id = null;
        return $this;
    }

    /**
     * Получить информацию о последнем запросе
     * 
     * @return array Информация о запросе
     */
    public function getRequestInfo(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'headers' => $this->headers,
            'query_params' => $this->queryParams,
            'full_url' => $this->baseUrl . $this->endpoint . '?' . http_build_query($this->queryParams)
        ];
    }
}