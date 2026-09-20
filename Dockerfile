FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql

# Nonaktifkan semua modul mpm yang ada dan aktifkan mpm_prefork secara bersih
RUN a2dismod -m mpm_event mpm_worker mpm_prefork || true
RUN a2enmod mpm_prefork

COPY . /var/www/html
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
EXPOSE 80
CMD ["apache2-foreground"]