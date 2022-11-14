FROM php:8.1-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid
ARG gid

ADD "container/config" "$PHP_INI_DIR/conf.d/"

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    openssl

# install libevent and ev php extension
RUN apt-get install -y libevent-dev \
    && pecl install ev \
    && docker-php-ext-enable ev

# Install mysql-client
RUN apt-get install -y \
    default-mysql-client

# Nodejs and npm installation
RUN apt-get install -y nodejs npm

# Install supervisor
RUN apt-get install -y supervisor

# hunspell and russian dictionary installation
RUN apt-get install -y hunspell hunspell-ru

# Install PrimeModule for AuthKey generation speedup
RUN git clone https://github.com/danog/PrimeModule-ext \
    && cd PrimeModule-ext && make -j$(nproc) \
    && make install \
    && cd ../  \
    && rm -rf PrimeModule-ext/ \

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sysvmsg sysvsem sysvshm sockets

RUN pecl install -o -f redis \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN mkdir -p /home/$user/.composer

# Create system user to run Composer and Artisan Commands
RUN addgroup --gid $gid $user && \
    adduser --uid $uid --gid $gid --disabled-password --home /home/$user $user && \
    adduser www-data $user && \
    chown -R $user:$user /var/www && \
    chown -R $user:$user /home/$user && \
    chown -R $user:$user /var/log/supervisor

# Set working directory
WORKDIR /var/www

USER $user
