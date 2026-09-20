FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql

# Salin seluruh file proyek
COPY . /var/www/html

# Karena struktur index.php Laravel mencari folder vendor di level atas public (/var/www/html/vendor),
# kita ubah konfigurasi virtualhost Apache agar DocumentRoot langsung menunjuk ke /var/www/html tanpa masuk ke folder public,
# tapi kita arahkan index nya menggunakan konfigurasi direktori.
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Buat symlink untuk folder vendor dan storage agar dapat diakses dengan benar
RUN rm -rf /var/www/html/public/vendor && ln -s /var/www/html/vendor /var/www/html/public/vendor

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Gunakan entrypoint untuk memastikan modul mpm prefork bersih dari konflik
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/' >> /usr/local/bin/entrypoint.sh && \
    echo 'exec apache2-foreground' >> /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]