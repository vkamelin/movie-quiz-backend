#!/bin/sh
set -e

# Ensure log directories exist and writable
mkdir -p /var/www/html/storage/logs /var/www/html/runtime/logs
chown -R www-data:www-data /var/www/html/storage/logs /var/www/html/runtime/logs

echo "==> Start php-fpm"
/usr/local/sbin/php-fpm --nodaemonize &

echo "==> Start supervisord"
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf
