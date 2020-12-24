FROM php:7.4-cli

# Install PHP extensions and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev mariadb-client libmemcached-dev libzip-dev zlib1g-dev libicu-dev \
    && pecl install                               memcached redis apcu \
    && docker-php-ext-enable                      memcached redis apcu \
    && docker-php-ext-configure                   gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)          gd bcmath zip intl pdo_mysql pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
