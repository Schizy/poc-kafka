FROM php:8.4-cli

# Le -dev est obligatoire pour demander le code source des librairies requises
# Sinon on aurait des binaires déjà compilés, donc inutilisables pour compiler les extensions PHP
RUN apt-get update && apt-get install -y git unzip libpq-dev librdkafka-dev \
    && docker-php-ext-install pcntl \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
