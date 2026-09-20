FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork
COPY . /var/www/html
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache