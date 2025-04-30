FROM php:8.4.6-apache

USER root

WORKDIR /var/www/html

# Install dependencies and PHP extensions including Xdebug
RUN apt-get update && apt-get install -y \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        zip \
        curl \
        unzip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-source delete

# Copy Apache config
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Composer setup
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permissions and enable rewrite
RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Add Xdebug config (optional - customize host/IP as needed)
COPY .docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
