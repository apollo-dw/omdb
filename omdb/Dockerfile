FROM node:latest AS node
FROM composer:latest AS composer
FROM php:8.2-fpm

RUN apt-get update -y && apt-get install -y --no-install-recommends \
    git \
    zip \
    unzip \
    ;

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN mkdir -p /code
WORKDIR /code

# COPY composer.json composer.lock /code
COPY . /code
RUN composer install --optimize-autoloader --no-dev

RUN php artisan config:cache
RUN php artisan view:cache

EXPOSE 8000
CMD ["bash", "-c", "php artisan migrate --force && php artisan serve --host 0.0.0.0"]
