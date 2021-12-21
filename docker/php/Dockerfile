FROM php:7.4-fpm

# Install DevContainer/Development tools and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client vim unzip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Required extensions and depends
RUN apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-configure                gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)       gd bcmath zip intl

# Install MySQL/MariaDB extensions and depends
RUN apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install -j$(nproc)       pdo_mysql

# Install PostgreSQL extensions and depends
RUN docker-php-ext-install -j$(nproc) pdo_pgsql

# Install SQL Server extensions and depends
RUN apt-get install -y                  gnupg \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/ubuntu/21.04/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && apt-get install -y               unixodbc-dev \
    && ACCEPT_EULA=Y apt-get install -y mssql-tools \
    && pecl install                     sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable            sqlsrv pdo_sqlsrv

# Install Memcached extensions and depends
RUN apt-get install -y --no-install-recommends libmemcached-dev \
    && pecl install                            memcached \
    && docker-php-ext-enable                   memcached

# Install Redis extensions and depends
RUN pecl install             redis \
    && docker-php-ext-enable redis

# Install Apcu extensions and depends
RUN pecl install             apcu \
    && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install SQLite 3 for Unit Test
RUN DEBIAN_FRONTEND=noninteractive apt-get -yq --no-install-recommends install sqlite3=3.* \
	&& mkdir -p /tmp/sqlite
COPY ./sqlite /tmp/sqlite
RUN echo ".open /tmp/sqlite/rebet.db" | sqlite3 \
    && sqlite3 /tmp/sqlite/rebet.db < /tmp/sqlite/initdb.d/create_tables.sql

# Clean
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*
