# Архитектура backend-каркаса: устройство, потоки и технические решения

## 1) Цель документа

Этот документ описывает **архитектуру платформенного каркаса** репозитория: как устроены слои, какие точки входа существуют, как проходят запросы и фоновые задачи, где находятся ключевые модули и как расширять систему без нарушения текущих практик.

Документ специально написан **без привязки к конкретному бизнес-сценарию**, чтобы его можно было использовать как базу для онбординга разработчиков и для анализа нейросетями.

---

## 2) Технологический профиль

### Ядро
- **PHP 8.3**.
- **Slim 4** как HTTP-микрофреймворк (PSR-7/PSR-15).
- **PHP-DI** для контейнера зависимостей и автовайринга.

### Данные и инфраструктура
- **MySQL/MariaDB** через **PDO** (без ORM).
- **Redis** (ext-redis) для очередей, limiter-ключей, dedup/idempotency и runtime-структур.
- **Phinx** для миграций схемы БД.

### Интеграции и сервисы
- **Longman Telegram Bot SDK** для Telegram API.
- **JWT** (firebase/php-jwt) для API-авторизации.
- **Monolog** для логирования.
- **Dotenv** для загрузки окружения.

### Эксплуатация
- **Docker** для окружения.
- **Supervisor** для фоновых процессов.
- Конфиги наблюдаемости для **Prometheus/Grafana** в `docker/*`.

---

## 3) Карта каталогов (что где находится)

- `public/`
  - `index.php` — основной HTTP entrypoint.
- `app/`
  - `Config/` — сборка конфигурации, DI-определения, роут-карта.
  - `Controllers/Api/` — API-контроллеры (JSON/RFC7807).
  - `Controllers/Dashboard/` — контроллеры server-rendered панели.
  - `Middleware/` — инфраструктурные и security middleware.
  - `Helpers/` — технические сервисы (DB, response, logging, push, redis-утилиты и т.д.).
  - `Handlers/` — обработчики событий/обновлений и фоновой бизнес-логики.
  - `Console/` — консольное ядро и команды.
  - `Telegram/` — фильтрация/утилиты обработки Telegram updates.
  - `Schemas/` — JSON Schema для валидации отдельных payload.
- `workers/`
  - отдельные CLI-entrypoints для долгоживущих процессов.
- `database/migrations/`
  - миграции Phinx.
- `templates/`
  - PHP-шаблоны dashboard-интерфейса.
- `docker/`
  - конфиги веб-сервера, php, supervisor, prometheus, grafana.
- `tests/`
  - `Unit/` и `Smoke/` тесты.
- `run`
  - единый запускатор консольных команд (`App\Console\Kernel`).

---

## 4) Точки входа (runtime surfaces)

Система имеет три независимые runtime-поверхности:

1. **HTTP runtime**: `public/index.php`.
2. **Console runtime**: `run` → `App\Console\Kernel`.
3. **Worker runtime**: `workers/*.php` (каждый процесс со своим циклом и lifecycle).

Это важный архитектурный принцип: web-запросы и длительная обработка разведены по процессам.

---

## 5) HTTP bootstrap и pipeline

### 5.1 Инициализация приложения (`public/index.php`)

Последовательность:
1. `vendor/autoload.php`.
2. `Dotenv::createImmutable(...)->safeLoad()`.
3. Загрузка `app/Config/config.php`.
4. Создание DI контейнера и регистрация определений `ContainerConfig::getDefinitions()`.
5. Создание Slim app через `AppFactory`.
6. Подключение глобальных middleware.
7. Регистрация маршрутов из `app/Config/routes.php` через универсальный регистратор.
8. `$app->run()`.

### 5.2 Глобальные middleware (порядок важен)

В `public/index.php` добавляются:
- `RequestIdMiddleware` — корреляция запроса и логов (`X-Request-Id`).
- `RequestSizeLimitMiddleware` — глобальный лимит тела + path overrides.
- `BodyParsingMiddleware` — парсинг JSON/form payload.
- `ErrorMiddleware` — централизованная ошибка (problem details).
- `SecurityHeadersMiddleware` — CORS/CSP/X-headers.

### 5.3 Почему это важно

- Безопасность и технические гарантии выполняются **до контроллеров**.
- Контроллеры получают уже нормализованный запрос с предсказуемыми инвариантами.

---

## 6) Конфигурация

### 6.1 Источник параметров
- Основной источник — `.env`.
- Аггрегация настроек — `app/Config/config.php`.

### 6.2 Ключевые группы параметров

- `db`: DSN/user/pass/PDO options.
  - Поддержка TCP и socket-подключения.
  - PDO настроен на strict mode (`ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`).
- `jwt`: secret/alg/ttl/refresh_ttl.
- `cors`: origins/methods/headers.
- `rate_limit`: bucket/limit/window/prefix.
- `request_size_limit` + `request_size_overrides`.
- служебные TTL (например, idempotency key).

### 6.3 Практика проекта

Слой данных в проекте строится на **PDO + подготовленные запросы**, без ORM-абстракций. Это системная практика репозитория.

---

## 7) DI контейнер и создание зависимостей

`app/Config/ContainerConfig.php` регистрирует инфраструктурные сервисы:
- `PDO::class` как singleton из runtime config.
- `Database::class` (helper-доступ к singleton PDO).
- `RateLimitMiddleware::class` с параметрами из config.
- `JwtMiddleware::class` с runtime jwt-конфигом.
- `TelegramInitDataMiddleware::class` с токеном бота.

Контроллеры вручную не перечисляются: используется автовайринг PHP-DI.

---

## 8) Маршрутизация: декларативная и многоуровневая

Маршруты описываются в `app/Config/routes.php` как декларативная структура:
- префикс зоны (`dashboard`, `api`);
- middleware зоны;
- список роутов;
- вложенные подгруппы с отдельным middleware.

Универсальный регистратор в `public/index.php` поддерживает:
- обычные маршруты `['GET', '/path', handler]`;
- `MAP` для нескольких HTTP-методов;
- middleware на маршрут;
- nested groups.

Это позволяет масштабировать карту роутов без разрастания bootstrap-кода.

---

## 9) Разделение зон доступа

### 9.1 Dashboard-зона

Типовой стек middleware:
- `SessionMiddleware`
- `CsrfMiddleware`
- `AuthMiddleware`

Назначение: server-rendered интерфейс с сессионной аутентификацией и CSRF-защитой форм.

### 9.2 API-зона

Для защищённых endpoint используются сочетания:
- `JwtMiddleware`
- `RateLimitMiddleware`
- интеграционные middleware (например, подписи провайдеров).

Назначение: stateless доступ, контролируемая частота запросов, проверка подписи/токена.

---

## 10) Middleware-слой (инфраструктурная матрица)

Каталог: `app/Middleware/`

- `ErrorMiddleware` — единая схема ошибок.
- `SecurityHeadersMiddleware` — безопасные заголовки + CORS/CSP.
- `RequestSizeLimitMiddleware` — защита от oversized запросов.
- `RequestIdMiddleware` — трассировка запроса.
- `SessionMiddleware` — сессии dashboard-потока.
- `CsrfMiddleware` — anti-CSRF.
- `AuthMiddleware` — проверка доступа в приватные UI-роуты.
- `JwtMiddleware` — авторизация API.
- `RateLimitMiddleware` — ограничение частоты.
- `TelegramInitDataMiddleware` — верификация Telegram initData.
- `VkSignatureMiddleware` — проверка подписи интеграции VK.

Архитектурно middleware отвечают за “cross-cutting concerns”, а не за бизнес-логику.

---

## 11) Контроллеры и представления

### 11.1 API-контроллеры
- Расположение: `app/Controllers/Api/`.
- Роль: принимать JSON, валидировать вход, вызывать сервисы/хелперы, возвращать JSON/problem+json.

### 11.2 Dashboard-контроллеры
- Расположение: `app/Controllers/Dashboard/`.
- Роль: формировать данные для HTML-страниц, обрабатывать формы и действия оператора.

### 11.3 Шаблоны
- Расположение: `templates/`.
- Подход: нативный PHP template rendering (layouts/partials/dashboard pages).

---

## 12) Слой данных

### 12.1 Доступ к БД

- Через PDO.
- Подготовленные запросы и типизированные bind-параметры.
- SQL хранится в коде контроллеров/хелперов/воркеров (без ORM).

### 12.2 Миграции

- Каталог: `database/migrations/`.
- Инструмент: Phinx (`vendor/bin/phinx ...`).
- Миграции — единственный источник изменения схемы в tracked-формате.

### 12.3 Почему так

- Полный контроль над SQL.
- Предсказуемая производительность.
- Минимум скрытой магии.

---

## 13) Redis в архитектуре

Redis используется как runtime-компонент для:
- очередей отправки;
- stream-пайплайнов обработки обновлений;
- dedup/idempotency ключей;
- rate-limit счётчиков;
- временных данных фоновых процессов.

Ключи формируются через helper-утилиты (`RedisHelper`, `RedisKeyHelper`), чтобы стандартизировать namespace.

---

## 14) Фоновые процессы (workers)

Каталог `workers/` содержит независимые демоны.

### 14.1 `workers/longpolling.php`

Функция:
- читает обновления от Telegram (`getUpdates`, offset, allowed_updates);
- фильтрует через `App\Telegram\UpdateFilter`;
- публикует обработку в Redis Stream (consumer-group подход);
- поддерживает graceful shutdown (`SIGTERM`, `SIGINT`), retry и reconnect.

Ключевые архитектурные элементы:
- backoff при сбоях Redis;
- хранение offset;
- защита от шумных падений за счёт retry-цикла.

### 14.2 `workers/handler.php`

Функция:
- читает элементы из Redis Stream как consumer;
- извлекает payload update;
- маршрутизирует в нужный обработчик (`app/Handlers/Telegram/*`);
- подтверждает/обрабатывает записи с учетом pending/ack логики stream.

### 14.3 `workers/telegram.php`

Функция:
- отправка исходящих сообщений из очередей;
- дедупликация сообщений через Redis key NX + TTL;
- ретраи с backoff;
- DLQ при исчерпании попыток;
- фиксация статуса отправки в БД.

### 14.4 `workers/scheduled_dispatcher.php`

Функция:
- выбирает отложенные задания, срок которых наступил;
- фиксирует блокировку/статус, чтобы не дублировать отправку;
- вычисляет получателей по target-стратегии;
- ставит персональные задачи в очередь через helper push.

### 14.5 Прочие воркеры

- `workers/purge_refresh_tokens.php` — периодическая очистка refresh-токенов.
- `workers/gpt.php` — обработка задач LLM из Redis-очереди, сохранение результатов и жизненного цикла task-ключей.

---

## 15) Telegram update pipeline (концептуально)

1. **Получение:** `longpolling.php` получает `Update[]`.
2. **Фильтрация:** `UpdateFilter::shouldProcess(...)` проверяет allow/deny (тип, чат, команда).
3. **Постановка:** принятые апдейты попадают в stream.
4. **Обработка:** `handler.php` делегирует в конкретный handler-класс.
5. **Исходящие действия:** handler может поставить push-задачу в очередь.
6. **Отправка:** `telegram.php` отправляет в API и обновляет статусы/ошибки.

Это разделяет ingest и processing, обеспечивая масштабирование по воркерам.

---

## 16) Фильтрация обновлений Telegram

`app/Telegram/UpdateFilter.php` поддерживает две модели конфигурации:

1. **ENV-списки** (`TG_ALLOW_*`, `TG_DENY_*`).
2. **Redis sets** при `TG_FILTERS_FROM_REDIS=true`.

Проверки выполняются в порядке:
- тип update;
- chat id;
- команда.

Логи skip-событий дебаунсятся, чтобы не зашумлять лог при массовой фильтрации.

---

## 17) Консольный слой

- Точка входа: `run`.
- Ядро: `app/Console/Kernel.php`.
- Команды: `app/Console/Commands/*`.

`Kernel` хранит реестр доступных команд и диспетчеризует запуск по `signature`.

Практически это слой административных операций: миграции, очистки, обслуживание очередей и запуск worker-oriented команд.

---

## 18) Логирование, диагностика, мониторинг

### 18.1 Логи
- Основной интерфейс: `App\Helpers\Logger` (Monolog под капотом).
- В воркерах логируются только значимые события/ошибки (уменьшение шума).

### 18.2 Корреляция
- `RequestIdMiddleware` унифицирует request-id для трассировки web-запросов.

### 18.3 Мониторинг
- В `docker/prometheus` и `docker/grafana` подготовлены конфиги инфраструктурного мониторинга.
- В коде есть точки интеграции телеметрии (`App\Telemetry`).

---

## 19) Надёжность и эксплуатационные инварианты

Система опирается на набор инвариантов:

- **Идемпотентность**: dedup ключи в Redis для исходящих задач.
- **Повторяемость**: retry/backoff в очередях.
- **Изоляция ответственности**: web, console и workers разделены.
- **Fail-safe при зависимостях**: graceful degradation в сценариях временной недоступности Redis.
- **Операционная управляемость**: Supervisor + Docker конфиги для воспроизводимого запуска.

---

## 20) Тесты и качество

- `tests/Unit/*` — покрытие инфраструктурных компонентов (middleware, handlers, helpers).
- `tests/Smoke/*` — базовые smoke-проверки API-поведения.
- Инструменты качества:
  - PHPUnit;
  - PHPStan;
  - Psalm;
  - PHP CS Fixer.

Цель: сохранить стабильность платформенного ядра при расширении прикладной логики.

---

## 21) Практика расширения (как добавлять новое)

### Новый API endpoint
1. Добавить контроллер в `app/Controllers/Api/`.
2. Зарегистрировать маршрут в `app/Config/routes.php`.
3. Подключить нужные middleware на уровне маршрута/группы.
4. Использовать существующие helper-сервисы (PDO/response/logger), не вводя альтернативный data-layer.

### Новый фоновой процесс
1. Добавить entrypoint в `workers/`.
2. Использовать существующие Redis/Logger/Database helpers.
3. Обеспечить retry/backoff и корректный shutdown.
4. Добавить конфиг процесса в Supervisor (`docker/supervisor/conf.d/*`).

### Новый тип обработчика событий
1. Добавить handler-класс в `app/Handlers/...`.
2. Встроить маршрутизацию в worker/dispatcher слой.
3. Сохранить единый стиль обработки ошибок и логирования.

---

## 22) Краткое резюме архитектуры

Это минималистичный, но production-ориентированный PHP-каркас с чётким разделением:
- HTTP-обработка (Slim + middleware);
- консольное администрирование (Kernel/Commands);
- асинхронные воркеры (Redis + handlers).

Ключевые технические решения: **PDO без ORM**, декларативный роутинг, middleware-first безопасность, Redis-очереди и потоковая обработка событий, а также контейнеризация и управляемый процессный runtime.
