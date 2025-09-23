#!/bin/bash

# Only change permissions for storage directory that needs write access
chmod -R 775 /var/www/html/storage 2>/dev/null || true
chmod -R 775 /var/www/html/bootstrap/cache 2>/dev/null || true

# Ensure composer dependencies are installed
composer install --no-scripts --no-interaction --prefer-dist

exec php-fpm