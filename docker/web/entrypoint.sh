#!/bin/bash

# Only change permissions for storage and bootstrap/cache directories that need write access
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# Ensure composer dependencies are installed
composer install --no-scripts --no-interaction --prefer-dist

# Run your Laravel artisan command
php artisan migrate:fresh --seed

exec php-fpm