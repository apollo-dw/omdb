FROM node:latest AS node
FROM composer:latest AS composer
FROM php:8.2-fpm

RUN apt-get update -y && apt-get install -y --no-install-recommends \
    tmux

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN mkdir -p /code
COPY . /code
WORKDIR /code

EXPOSE 8000
CMD ["php", "artisan", "serve"]
