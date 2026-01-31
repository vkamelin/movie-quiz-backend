@echo off

echo [Docker] build and start
docker compose up -d --build
docker compose exec app php vendor/bin/phinx migrate
echo Done.
pause
