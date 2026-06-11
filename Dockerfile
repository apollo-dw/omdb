FROM php:7.4.3-apache
RUN docker-php-ext-install mysqli
RUN a2enmod rewrite
