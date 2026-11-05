#!/bin/sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required" >&2
    exit 1
fi

PORT="${PORT:-8080}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database
touch database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database

php artisan optimize:clear >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    attempt=1
    until php artisan migrate --force --no-interaction; do
        if [ "${attempt}" -ge 10 ]; then
            echo "Migrations failed after ${attempt} attempts" >&2
            exit 1
        fi
        echo "Database not ready, retrying (${attempt}/10)..."
        attempt=$((attempt + 1))
        sleep 3
    done
fi

php artisan config:cache
php artisan view:cache

exec apache2-foreground
