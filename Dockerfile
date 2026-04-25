FROM php:8.2-apache

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Railway
# ============================================

# Install ekstensi PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Fix "More than one MPM loaded":
# Hapus SEMUA symlink MPM yang aktif, lalu aktifkan hanya mpm_prefork.
# Cara ini lebih reliable dibanding a2dismod yang bisa silent fail.
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf \
 && ln -s /etc/apache2/mods-available/mpm_prefork.load \
          /etc/apache2/mods-enabled/mpm_prefork.load \
 && ln -s /etc/apache2/mods-available/mpm_prefork.conf \
          /etc/apache2/mods-enabled/mpm_prefork.conf \
 && a2enmod rewrite

# Copy semua file project ke document root Apache
COPY . /var/www/html/

# Set permission agar Apache bisa membaca file
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Izinkan .htaccess berfungsi (AllowOverride All)
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

