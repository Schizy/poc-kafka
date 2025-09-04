FROM php:8.2-cli

RUN apt-get update && apt-get install -y git unzip libpq-dev librdkafka-dev \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
