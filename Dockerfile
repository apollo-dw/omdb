FROM php:8.1.2-apache
RUN docker-php-ext-install mysqli
RUN a2enmod rewrite
