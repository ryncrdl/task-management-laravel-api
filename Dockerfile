FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev)
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-interaction

# Copy application source
COPY . .

# Run post-install scripts now that full app is present
RUN composer run-script post-autoload-dump

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# At container start: inject real env vars into cache, migrate, then serve
CMD php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache \
    && php artisan migrate --force \
    && php artisan serve --host=0.0.0.0 --port=8000
