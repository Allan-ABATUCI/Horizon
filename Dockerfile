# syntax=docker/dockerfile:1

# --- Étape 1 : build des assets front (Vite/React) ---
FROM node:22-alpine AS frontend
WORKDIR /app
COPY . .
RUN npm ci && npm run build

# --- Étape 2 : image PHP finale (Apache + mod_php) ---
FROM php:8.4-apache AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libsqlite3-dev libzip-dev libicu-dev unzip git \
    && docker-php-ext-install pdo_sqlite zip intl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=frontend /app/public/build public/build

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public/!g' /etc/apache2/apache2.conf \
    && printf '<Directory /var/www/html/public>\n\tAllowOverride All\n</Directory>\n' >> /etc/apache2/apache2.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
