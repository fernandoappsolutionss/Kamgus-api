# =============================================================================
# Dockerfile for Kamgus API on Railway
# Stack: PHP 8.1 + Apache + Composer
# Document root: public/ (Laravel standard)
# =============================================================================

FROM php:8.1-apache

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
# Composer (latest)
# -----------------------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# Apache configuration
# -----------------------------------------------------------------------------
RUN a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides (needed by Laravel's public/.htaccess)
RUN { \
        echo '<Directory ${APACHE_DOCUMENT_ROOT}>'; \
        echo '  AllowOverride All'; \
        echo '  Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

# -----------------------------------------------------------------------------
# Application
# -----------------------------------------------------------------------------
WORKDIR /var/www/html

# Copy composer files first to leverage Docker layer cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Now copy the rest
COPY . .

# Finalize composer (autoload, scripts)
RUN composer dump-autoload --optimize --no-dev

# Permissions for Laravel runtime dirs
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# -----------------------------------------------------------------------------
# Apache port: Railway provides $PORT env var (usually 8080)
# -----------------------------------------------------------------------------
ENV PORT=8080
RUN sed -i "s/Listen 80/Listen \${PORT}/" /etc/apache2/ports.conf \
 && sed -i "s/<VirtualHost \*:80>/<VirtualHost *:\${PORT}>/" /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# -----------------------------------------------------------------------------
# Startup: cache configs (idempotent on every restart) then run Apache foreground
# -----------------------------------------------------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
