#!/bin/sh
set -e

cd /var/www/html

if [ "$1" = "queue:work" ] || [ "$1" = "schedule:loop" ]; then
    shift
    exec php artisan "$@"
fi

php artisan storage:link --force 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec /init
