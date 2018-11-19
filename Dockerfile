FROM php:5.6-apache

RUN apt-get update && apt-get install -y libxml++2.6-dev \
    && docker-php-ext-install soap

RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
    && pecl install memcached-2.2.0 \
    && docker-php-ext-enable memcached

COPY . /var/www/html/

EXPOSE 80
