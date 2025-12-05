# backend/Dockerfile
FROM php:8.2-fpm

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip libpng-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip

# Composer をコピー（composerイメージから取り出す）
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# composer install（vendor は .dockerignore で無視している前提）
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan key:generate || true

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]