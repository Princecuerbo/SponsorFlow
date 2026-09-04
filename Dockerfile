FROM php:8.2-fpm

# Install system dependencies & PostgreSQL dev libraries
RUN apt-get update && apt-get install -y \
    nginx \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql pgsql pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy workspace code
COPY . .

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader

# Hardened directory permissions for www-data
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Apply custom Nginx config
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Make startup script executable
RUN chmod +x /var/www/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/entrypoint.sh"]