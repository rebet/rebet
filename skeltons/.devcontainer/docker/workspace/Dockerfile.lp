FROM php:8.3-cli

# Use bash
SHELL ["/bin/bash", "-c"]

# Set Args Defaults
ARG USER_NAME=vscode
ARG USER_UID=1000
ARG USER_GID=${USER_UID}

# Install composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install DevContainer/Development tools and depends
RUN apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client vim unzip jq \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Required extensions and depends
RUN apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpq-dev libzip-dev libicu-dev libcurl4 libcurl4-openssl-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure            gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc)   gd bcmath zip curl intl mbstring xml sockets

# Install mailhog modules
RUN curl -sSL https://github.com/mailhog/mhsendmail/releases/download/v0.2.0/mhsendmail_linux_amd64 -o mhsendmail \
    && chmod +x mhsendmail \
    && mv mhsendmail /usr/local/bin/mhsendmail

# Install stable version of Node.js and npm
RUN apt-get install -y --no-install-recommends curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@latest

# Install GitHub CLI
RUN (type -p wget >/dev/null || apt-get install -y wget) \
    && mkdir -p -m 755 /etc/apt/keyrings \
    && mkdir -p -m 755 /etc/apt/sources.list.d \
    && wget -nv -O /tmp/githubcli-archive-keyring.gpg https://cli.github.com/packages/githubcli-archive-keyring.gpg \
    && cat /tmp/githubcli-archive-keyring.gpg | tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
    && chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
    && apt-get update \
    && apt-get install -y --no-install-recommends gh \
    && rm -f /tmp/githubcli-archive-keyring.gpg

#{%-- if $database == 'mysql' -%}
# Install MySQL/MariaDB extensions and depends
RUN docker-php-ext-install -j$(nproc) pdo_mysql

#{%-- endif -%}
#{%-- if $database == 'mariadb' -%}
# Install MariaDB extensions and depends
RUN apt-get install -y --no-install-recommends mariadb-client \
    && docker-php-ext-install -j$(nproc)       pdo_mysql

#{%-- endif -%}
#{%-- if $database == 'pgsql' -%}
# Install PostgreSQL extensions and depends
RUN docker-php-ext-install -j$(nproc) pdo_pgsql

#{%-- endif -%}
#{%-- if $cache == 'memcached' -%}
# Install Memcached extensions and depends
RUN apt-get install -y --no-install-recommends libmemcached-dev \
    && pecl install                            memcached \
    && docker-php-ext-enable                   memcached

#{%-- endif -%}
#{%-- if $cache == 'redis' -%}
# Install Redis extensions and depends
RUN pecl install             redis \
    && docker-php-ext-enable redis

#{%-- endif -%}
#{%-- if $cache == 'apcu' -%}
# Install Apcu extensions and depends
RUN pecl install             apcu \
    && docker-php-ext-enable apcu

#{%-- endif -%}
# Clean
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create User
RUN groupadd --gid ${USER_GID} ${USER_NAME} \
    && useradd -s /bin/bash --uid ${USER_UID} --gid ${USER_GID} -m ${USER_NAME} \
    && apt-get update \ 
    && apt-get install -y sudo \
    && echo ${USER_NAME} ALL=\(root\) NOPASSWD:ALL > /etc/sudoers.d/${USER_NAME} \
    && chmod 0440 /etc/sudoers.d/${USER_NAME}
