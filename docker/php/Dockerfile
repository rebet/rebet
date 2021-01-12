FROM php:7.4-cli

# Install PHP extensions and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev mariadb-client libmemcached-dev libzip-dev zlib1g-dev libicu-dev \
    && pecl install                               memcached redis apcu \
    && docker-php-ext-enable                      memcached redis apcu \
    && docker-php-ext-configure                   gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)          gd bcmath zip intl pdo_mysql pdo_pgsql

# Install SQL Server extensions and depends
RUN apt-get install -y                 gnupg \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools \
    && apt-get install -y               unixodbc-dev libgssapi-krb5-2 \
    && pecl install                     sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable            sqlsrv pdo_sqlsrv

# Clean
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*
