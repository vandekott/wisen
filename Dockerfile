FROM php:8.1-fpm

# Arguments defined in .docker.yml
ARG user
ARG uid

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

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sysvmsg sysvsem sysvshm sockets

RUN pecl install -o -f redis \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user && \
    chown -R $user:$user /var/www


# Set working directory
WORKDIR /var/www

USER $user