# syntax=docker/dockerfile:1

# --- Étape 1 : build des assets front (Vite/React) ---
FROM node:22-alpine AS frontend
WORKDIR /app
COPY . .
RUN npm ci && npm run build

# --- Étape 2 : image PHP finale (Alpine + PHP-FPM + nginx) ---
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache nginx supervisor

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-production.ini "$PHP_INI_DIR/conf.d/99-horizon.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public/build public/build

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
