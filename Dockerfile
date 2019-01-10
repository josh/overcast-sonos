FROM php:7.2-apache

RUN apt-get update && apt-get install -y libxml2-dev \
    && docker-php-ext-install soap

RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
    && pecl install memcached-3.0.4 \
    && docker-php-ext-enable memcached

RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/timezone.ini

COPY . /var/www/html/

EXPOSE 80
