# =============================================================================
# Dockerfile for Kamgus API on Railway
# Uses PHP 8.1 CLI + built-in HTTP server (no Apache, no MPM headaches)
# Document root: public/  ·  Router: server.php (Laravel-provided)
# =============================================================================

FROM php:8.1-cli

# -----------------------------------------------------------------------------
# System dependencies
# -----------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        zip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libpq-dev \
        libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------------------
# PHP extensions: required by Laravel + Postgres + Stripe + push notifications
# -----------------------------------------------------------------------------
RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# -----------------------------------------------------------------------------
# Composer
# -----------------------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# Application
# -----------------------------------------------------------------------------
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# -----------------------------------------------------------------------------
# Networking
# -----------------------------------------------------------------------------
ENV PORT=8080
EXPOSE 8080

# -----------------------------------------------------------------------------
# Startup
# -----------------------------------------------------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
