FROM php:8.4-cli

RUN apt-get update && apt-get install -y git unzip libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && touch database/database.sqlite \
    && php artisan migrate --force --seed \
    && php artisan config:cache \
    && php artisan route:cache

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
