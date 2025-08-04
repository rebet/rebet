FROM php:7.4-cli

# Use bash
SHELL ["/bin/bash", "-c"]

# Set Args Defaults
ARG USER_NAME=vscode
ARG USER_UID=1000
ARG USER_GID=${USER_UID}

# Install DevContainer/Development tools and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends sudo git openssh-client vim unzip \
    && pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

# Create User
RUN groupadd --gid ${USER_GID} ${USER_NAME} \
    && useradd -s /bin/bash --uid ${USER_UID} --gid ${USER_GID} -m ${USER_NAME} \
    && echo ${USER_NAME} ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/${USER_NAME} \
    && chmod 0440 /etc/sudoers.d/${USER_NAME}

# Install Required extensions and depends
RUN apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-configure                gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)       gd bcmath zip intl

# Install MySQL/MariaDB extensions and depends
RUN apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install -j$(nproc)       pdo_mysql

# Install PostgreSQL extensions and depends
RUN docker-php-ext-install -j$(nproc) pdo_pgsql

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
    && sqlite3 /tmp/sqlite/rebet.db < /tmp/sqlite/initdb/create_tables.sql \
    && chown -R ${USER_NAME}:${USER_NAME} /tmp/sqlite

# Clean
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

