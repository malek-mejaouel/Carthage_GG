FROM php:8.2-fpm-alpine
RUN apk add --no-cache icu-dev libzip-dev git unzip bash \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl zip opcache \
    && rm -rf /var/cache/apk/*
WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN mkdir -p /usr/local/etc/php/conf.d \
    && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/zz-symfony.ini
