# Переменные окружения (.env)

Ниже перечислены ключевые переменные окружения с пояснениями и типичными значениями. Большая часть считывается в `app/Config/config.php`.

## Общие
- `APP_NAME`: имя приложения, например `telegram-bot`.
- `APP_ENV`: режим (`dev` или `prod`).
- `APP_URL`: базовый URL приложения.
- `WEB_APP_URL`: URL Telegram WebApp, если используется.

## Логи
- `LOG_CHANNEL`: канал логирования (например, `app`).
- `LOG_LEVEL`: уровень логирования (`DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY`).

## База данных (PDO)
- `DB_CONNECTION_TYPE`: `tcp` или `socket`.
- `DB_HOST`: хост БД (например, `127.0.0.1` или `db` в Docker).
- `DB_PORT`: порт БД (обычно `3306`).
- `DB_CHARSET`: кодировка, например `utf8mb4`.
- `DB_NAME`: имя базы, например `app`.
- `DB_USER`: пользователь БД.
- `DB_PASS`: пароль пользователя БД.

Пример DSN для MySQL: `mysql:host=127.0.0.1;dbname=app;charset=utf8mb4` (в коде собирается из переменных).

## Redis (опционально)
- `REDIS_HOST`: хост Redis (`127.0.0.1` или `redis`).
- `REDIS_PORT`: порт Redis (`6379`).
- `REDIS_DB`: номер БД Redis (`0`).
- `REDIS_PREFIX`: префикс ключей (например, `app:`).

## JWT
- `JWT_SECRET`: секрет подписи JWT. В проде должен быть длинным и случайным.
- `JWT_TTL`: время жизни JWT в секундах (умолчание 3600 в коде).
- `JWT_ALG`: алгоритм подписи (обычно `HS256`).

## CORS и CSP
- `CORS_ORIGINS`: список разрешённых origin через запятую (например, `https://example.com,https://app.example.com`). `*` для разработки.
- `CSP_SCRIPT_SRC`, `CSP_STYLE_SRC`, `CSP_FONT_SRC`: источники для CSP (по умолчанию разрешены популярные CDN, можно ужесточать).

## Rate‑limit и размер запроса
- `RATE_LIMIT_BUCKET`: `ip` или `user` — по чему лимитировать.
- `RATE_LIMIT`: число запросов за окно.
- `RATE_LIMIT_WINDOW_SEC`: длительность окна (секунды, по умолчанию 60).
- `RATE_LIMIT_REDIS_PREFIX`: префикс ключей в Redis для rate-limit (по умолчанию `rl:`).
- `REQUEST_SIZE_LIMIT`: максимальный размер тела запроса в байтах (по умолчанию 1048576).

## Telegram
- `BOT_API_SERVER`: `remote` или `local` — куда отправлять Bot API.
- `BOT_TOKEN`: токен Telegram‑бота.
- `BOT_NAME`: username бота (без `@`).
- `DEFAULT_CHAT_ID`: дефолтный чат для тестов/отправок по умолчанию (опционально).
- `BOT_MAX_RPS`: желаемое ограничение RPS (мягкая настройка клиента).
- `BOT_LOCAL_API_HOST`, `BOT_LOCAL_API_PORT`: параметры локального Bot API при `BOT_API_SERVER=local`.

## Фильтр апдейтов Telegram
- `TG_FILTERS_FROM_REDIS`: `true/false` — источник правил: Redis или `.env`.
- `TG_FILTERS_REDIS_PREFIX`: префикс ключей в Redis для списков (по умолчанию `tg:filters`).
- `TG_ALLOW_TYPES`, `TG_DENY_TYPES`: списки разрешённых/запрещённых типов апдейтов (через запятую).
- `TG_ALLOW_CHATS`, `TG_DENY_CHATS`: списки ID чатов (разрешить/запретить).
- `TG_ALLOW_COMMANDS`, `TG_DENY_COMMANDS`: списки команд бота (разрешить/запретить).

## Идемпотентность
- `IDEMPOTENCY_KEY_TTL`: TTL для ключей идемпотентности (секунды), по умолчанию 60.

## Телеметрия
- `TELEMETRY_ENABLED`: `true/false` — включает трейсинг/метрики (реализация заглушена, оставлена точка расширения).

## Воркеры
- `WORKERS_TELEGRAM_PROCS`: число процессов воркера Telegram.
- `WORKERS_GPT_PROCS`: число процессов воркера GPT.
- `WORKERS_SCHEDULED_PROCS`: число процессов планировщика.
- `SCHEDULED_DISPATCH_LIMIT`: лимит батча для отправки отложенных сообщений.

Дополнительно для проверки статуса воркеров через Supervisor из другого контейнера/хоста:
- `SUPERVISOR_SERVER_URL`: URL RPC Supervisor для `supervisorctl` (например, `http://supervisor:9001` при включённом `[inet_http_server]`, либо `unix:///var/run/supervisor.sock`).
- `SUPERVISOR_USER`: имя пользователя для доступа к Supervisor (опционально, если настроена базовая аутентификация).
- `SUPERVISOR_PASS`: пароль для доступа к Supervisor (опционально).

## Интеграции
- `AITUNNEL_API_KEY`: ключ для AITunnel (если используется).

## Передача initData (Telegram Mini App)
`TelegramInitDataMiddleware` валидирует подпись `initData` на основе `BOT_TOKEN`. В `Authorization: tma <initData>`, `X-Telegram-Init-Data` или параметром `initData` — любой из способов допустим.

Примеры:
```bash
curl http://localhost:8080/api/health -H "Authorization: tma <initData>"
curl http://localhost:8080/api/health -H "X-Telegram-Init-Data: <initData>"
curl "http://localhost:8080/api/health?initData=<initData>"
```
