FROM node:latest AS node
FROM composer:latest AS composer
FROM php:8.2-fpm

ARG APP_KEY
ARG DB_HOST
ARG DB_USERNAME
ARG DB_PASSWORD
ARG DB_DATABASE
ARG DB_CONNECTION
ARG OSU_CLIENT_ID
ARG OSU_CLIENT_SECRET

ENV APP_KEY=${APP_KEY}
ENV DB_HOST=${DB_HOST}
ENV DB_USERNAME=${DB_USERNAME}
ENV DB_PASSWORD=${DB_PASSWORD}
ENV DB_DATABASE=${DB_DATABASE}
ENV DB_CONNECTION=${DB_CONNECTION}
ENV OSU_CLIENT_ID=${OSU_CLIENT_ID}
ENV OSU_CLIENT_SECRET=${OSU_CLIENT_SECRET}

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

EXPOSE 8000
CMD ["bash", "-c", "php artisan config:cache; php artisan view:cache; php artisan serve --host 0.0.0.0"]
