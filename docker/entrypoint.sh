#!/bin/bash
set -e

# Railway injects $PORT — make Apache listen on it
PORT=${PORT:-80}
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure exactly one Apache MPM is enabled (mod_php requires prefork)
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

cd /app

# Ensure writable dirs exist (fresh container)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/app/private storage/app/public storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Cache config/routes now that production env vars are present
php artisan config:cache
php artisan route:cache

# Run migrations + seeders (idempotent). Retry while MySQL is still starting.
for i in $(seq 1 24); do
    if php artisan migrate --force --seed; then
        break
    fi
    echo "Database not ready yet, retrying in 5s... ($i/24)"
    sleep 5
done

# Public storage symlink for uploaded covers/files
php artisan storage:link 2>/dev/null || true

exec apache2-foreground
