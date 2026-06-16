FROM php:8.2-apache

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

WORKDIR /var/www/html

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --prefer-dist

EXPOSE 80