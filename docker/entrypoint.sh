#!/bin/sh
set -e

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

chown -R www-data:www-data storage bootstrap/cache database

if [ ! -L public/storage ]; then
    php artisan storage:link
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache

exec "$@"
