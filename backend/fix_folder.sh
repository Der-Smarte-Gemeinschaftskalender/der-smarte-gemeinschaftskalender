#!/bin/sh
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage/logs
chmod -R 775 /var/www/html/storage/logs

mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Rechte setzen (typisch für PHP-FPM/Apache = www-data)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

#jwt
php artisan key:generate --force
php artisan jwt:generate-certs --force
php artisan jwt:secret --force

# Artisan Caches leeren
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
