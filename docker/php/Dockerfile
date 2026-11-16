FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl libzip-dev libicu-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql zip pcntl bcmath intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/conf.d/reelhouse.ini
COPY entrypoint.sh /usr/local/bin/reelhouse-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/reelhouse-entrypoint && chmod +x /usr/local/bin/reelhouse-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/app
EXPOSE 8000

ENTRYPOINT ["reelhouse-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
