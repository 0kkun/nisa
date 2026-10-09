FROM php:8.5.11-fpm

COPY ./.docker/php/php.ini /usr/local/etc/php/

# for vite server
EXPOSE 5173

RUN set -x \
  && apt-get update \
  && apt-get install -y git libicu-dev libpng-dev libzip-dev libonig-dev wget zip unzip vim \
  && docker-php-ext-install bcmath exif gd intl pdo_mysql sockets zip \
  && mkdir -p /var/log/php

# Composer install
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
ENV PATH=$PATH:/composer/vendor/bin
COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer

# Install Node and npm
RUN curl -fsSL https://deb.nodesource.com/setup_26.x | bash - \
  && apt-get install -y nodejs

WORKDIR /var/www
