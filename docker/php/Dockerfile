FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git curl zip unzip libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-nilex.ini

RUN addgroup -g 1000 www && adduser -G www -g www -s /bin/sh -D www
USER www

COPY --chown=www:www . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

CMD ["php-fpm"]
