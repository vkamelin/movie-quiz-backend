#!/usr/bin/env bash
set -euo pipefail

# Режим развёртывания: docker или vps
MODE=${1:-docker}   # docker или vps

case "$MODE" in
  docker)
    echo "[Docker] сборка и запуск"
    docker compose up -d --build
    docker compose exec app php vendor/bin/phinx migrate || true
    ;;
  vps)
    echo "[VPS] composer и миграции"
    composer install --no-dev --optimize-autoloader
    vendor/bin/phinx migrate -e production || true
    ;;
  *)
    echo "использование: $0 [docker|vps]"
    exit 1
    ;;
esac

echo "Готово."
