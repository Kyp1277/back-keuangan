FROM php:8.2-apache

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Railway
# ============================================

# Install ekstensi PDO MySQL (dibutuhkan untuk koneksi database)
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite Apache (untuk routing URL yang bersih)
RUN a2enmod rewrite

# Copy semua file project ke document root Apache
COPY . /var/www/html/

# Set permission agar Apache bisa membaca file
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Izinkan .htaccess berfungsi
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80 (Railway akan map ini ke port publik)
EXPOSE 80
