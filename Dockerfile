FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Buat entrypoint script untuk membersihkan konflik MPM secara otomatis saat kontainer mulai berjalan
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'exec apache2-foreground' >> /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]