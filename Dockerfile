FROM php:5.6-apache

RUN apt-get update && apt-get install -y libxml++2.6-dev \
    && docker-php-ext-install soap

RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
    && pecl install memcached-2.2.0 \
    && docker-php-ext-enable memcached

RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/timezone.ini

COPY . /var/www/html/

EXPOSE 80

HEALTHCHECK CMD curl --fail "http://localhost/api.php?method=fetchPodcast&id=itunes617416468%2Faccidental-tech-podcast" || exit 1
