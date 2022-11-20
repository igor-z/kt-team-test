FROM php:8.1-fpm

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip libpq-dev librabbitmq-dev \
    && docker-php-ext-install intl opcache pdo pdo_pgsql \
    && pecl install apcu xdebug-3.1.6 amqp \
    && docker-php-ext-enable xdebug apcu amqp \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

WORKDIR /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

EXPOSE 9000