FROM php:8.2-apache

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Railway
# ============================================

# Install ekstensi PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Fix "More than one MPM loaded":
# Hapus file eksplisit (bukan glob) untuk mpm_event & mpm_worker,
# lalu aktifkan HANYA mpm_prefork.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
 && a2enmod mpm_prefork rewrite

# Izinkan .htaccess berfungsi
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy semua file project ke document root Apache
COPY . /var/www/html/

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Jalankan Apache secara foreground (wajib di Docker)
CMD ["apache2-foreground"]
