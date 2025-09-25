#!/bin/bash

# Create necessary directories if they don't exist
mkdir -p /var/www/html/vendor
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/app
mkdir -p /var/www/html/storage/app/public

# Set more permissive permissions for Laravel directories
chmod -R 777 /var/www/html/storage 2>/dev/null || true
chmod -R 777 /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/vendor 2>/dev/null || true

# Ensure composer dependencies are installed
composer install --no-scripts --no-interaction --prefer-dist

# Generate application key if not exists
php artisan key:generate --no-interaction
php artisan optimize:clear
# Run your Laravel artisan command
php artisan migrate

exec php-fpm