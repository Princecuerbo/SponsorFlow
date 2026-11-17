#!/bin/bash

# Fix permissions on startup
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Clear stale caches from any previous deploy
echo "Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Safely apply pending database migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed lookup tables & base state (safe to re-run via upserts)
echo "Seeding lookup data..."
php artisan db:seed --force

# Import institutional registry only when explicitly enabled.
if [ "${SPONSORFLOW_SEED_REGISTRY_ON_DEPLOY:-false}" = "true" ]; then
    REGISTRY_CSV="/etc/secrets/sle_fhe_registries_cleaned.csv"

    if [ ! -s "$REGISTRY_CSV" ]; then
        echo "ERROR: SLE-FHE registry Secret File is missing or empty." >&2
        exit 1
    fi

    echo "Running gated institutional SLE-FHE registry seeder..."
    php artisan db:seed --class=SleFheInstitutionalRegistrySeeder --force
fi

# Cache application state for production.
# view:cache is intentionally skipped: pre-compiling vendor Blade views (e.g.
# Laravel's exception renderer) fails with "Unable to locate a class or view for
# component", so views are compiled on demand at runtime instead (view:clear above).
echo "Caching config and routes for production..."
php artisan config:cache
php artisan route:cache

# Start Nginx and PHP-FPM
service nginx start
php-fpm