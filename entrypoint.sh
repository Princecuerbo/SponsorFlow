#!/bin/bash

# Fix permissions on startup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Safely apply pending database migrations
echo "Running database migrations..."
php artisan migrate:fresh --force

# Seed lookup tables & base state (safe to re-run via upserts)
echo "Seeding lookup data..."
php artisan db:seed --class=LocalAddressSeeder --force
php artisan db:seed --class=AcademicProgramSeeder --force
php artisan db:seed --class=RoleAndUserSeeder --force
php artisan db:seed --class=SponsorshipProgramSeeder --force

# Cache application state for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Nginx and PHP-FPM
service nginx start
php-fpm