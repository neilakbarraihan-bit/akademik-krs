FROM php:8.2-apache

# Install ekstensi PHP, Composer, dan unzip
RUN docker-php-ext-install pdo pdo_mysql
RUN apt-get update && apt-get install -y unzip git
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

# Install dependensi backend Composer di server
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Arahkan DocumentRoot Apache ke folder public Laravel
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Berikan izin penuh pada storage dan bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Entrypoint yang aman untuk environment production
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'php artisan config:clear' >> /usr/local/bin/entrypoint.sh && \
    echo 'export APP_KEY=$(php artisan key:generate --show)' >> /usr/local/bin/entrypoint.sh && \
    echo 'exec apache2-foreground' >> /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]