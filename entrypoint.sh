#!/bin/bash

# Fix permissions on startup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Safely apply new database migrations WITHOUT dropping existing user data
echo "Running database migrations..."
php artisan migrate --force

# Seed academic programs (safe to re-run because it checks/updates existing records)
echo "Seeding academic programs..."
php artisan db:seed --class=AcademicProgramSeeder --force

# Cache application state for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Nginx and PHP-FPM
service nginx start
php-fpm