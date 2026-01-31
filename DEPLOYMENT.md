# Развёртывание

Поддерживаются два типовых сценария: Docker и VPS без Docker.

## A. Docker

1) Подготовка
```bash
chmod +x docker/entrypoint.sh
```

2) Запуск
```bash
docker compose up -d --build
docker compose logs -f
```

Проверка:
- API: `http://localhost:8080/api/health`
- Dashboard: `http://localhost:8080/dashboard`

3) Миграции
```bash
docker compose exec app php run migrate:run
```

## B. VPS (без Docker)

1) Зависимости
- Nginx
- PHP‑FPM 8.3 (`php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-mysql`)
- Composer 2
- Supervisor (для воркеров)

2) Деплой
```bash
composer install --no-dev --optimize-autoloader
php run migrate:run
```

3) Nginx
Возьмите конфиг из `docker/nginx/default.conf` за основу и адаптируйте пути под ваш сервер. Сымлинкуйте и перезагрузите nginx:
```bash
ln -s /etc/nginx/sites-available/yourapp.conf /etc/nginx/sites-enabled/yourapp.conf
nginx -t && systemctl reload nginx
```

4) Supervisor
Скопируйте юниты из `docker/supervisor` в `/etc/supervisor/` и включите:
```bash
apt-get install -y supervisor
cp -r docker/supervisor/* /etc/supervisor/
supervisorctl reread
supervisorctl update
systemctl enable --now supervisor
```

5) Обновления
```bash
git pull
composer install --no-dev --optimize-autoloader
php run migrate:run
systemctl reload nginx && systemctl reload php8.3-fpm
supervisorctl reload
```

## Проверки
- `/api/health` возвращает `{"status":"ok"}`
- Dashboard открывается и авторизация работает
- Воркеры видны в `supervisorctl status`

