# MyEbookBazaar — Railway production image (PHP 8.3 + Apache)
FROM php:8.3-apache

# System dependencies and PHP extensions required by the app
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
        default-mysql-client \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring zip exif pcntl gd bcmath opcache \
    && a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true \
    && a2enmod mpm_prefork rewrite \
    && rm -rf /var/lib/apt/lists/*

# PHP upload limits for ebook PDFs (must cover the 200 MB BookRequest cap).
# Without these, PHP's 2M/8M defaults reject real ebook uploads before
# Laravel validation ever runs, and the admin sees a failed-upload error.
RUN printf 'upload_max_filesize = 200M\npost_max_size = 210M\nmemory_limit = 512M\nmax_execution_time = 300\n' \
    > /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# Install PHP dependencies (no dev packages in production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && php artisan view:cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Apache serves Laravel's public directory; allow .htaccess overrides
ENV APACHE_DOCUMENT_ROOT=/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /app/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]
