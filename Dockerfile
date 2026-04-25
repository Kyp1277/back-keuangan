FROM php:8.2-apache

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Railway
# ============================================

# Install ekstensi PDO MySQL (dibutuhkan untuk koneksi database)
RUN docker-php-ext-install pdo pdo_mysql

# Fix: "More than one MPM loaded"
# php:8.2-apache mengaktifkan mpm_event secara default,
# tapi mod_php butuh mpm_prefork. Nonaktifkan yang lama, aktifkan yang benar.
RUN a2dismod mpm_event && a2enmod mpm_prefork rewrite

# Copy semua file project ke document root Apache
COPY . /var/www/html/

# Set permission agar Apache bisa membaca file
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Izinkan .htaccess berfungsi
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80 (Railway akan map ini ke port publik)
EXPOSE 80
