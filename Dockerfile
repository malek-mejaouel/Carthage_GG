
# Dockerfile for Symfony app on Render
FROM php:8.2-fpm-alpine AS php_base

RUN apk add --no-cache icu-dev libzip-dev git unzip bash nginx supervisor \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl zip opcache \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /usr/local/etc/php/conf.d \
    && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/zz-symfony.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/zz-symfony.ini

# Copy nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy supervisor config
RUN mkdir -p /etc/supervisor/conf.d
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application files
COPY . .

# Create necessary directories and set permissions early
RUN mkdir -p var/cache var/log public/bundles public/assets \
    && chown -R www-data:www-data var public

# Set environment to prod for build
ENV APP_ENV=prod

# Fix composer config for Docker (remove Windows-specific settings)
RUN composer config --unset cafile && composer config --unset disable-tls

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-ansi --no-interaction --no-scripts

# Set permissions again
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/public

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
