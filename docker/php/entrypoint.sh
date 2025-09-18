#!/bin/bash
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage


# Ensure composer dependencies are installed
composer install --no-scripts --no-interaction --prefer-dist

# Run your Laravel artisan command
php artisan migrate:fresh --seed

exec php-fpm