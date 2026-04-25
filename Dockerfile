FROM php:8.2-cli

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Railway tanpa Apache
# ============================================

WORKDIR /app

# Install ekstensi PDO MySQL untuk koneksi database Railway/MySQL.
RUN docker-php-ext-install pdo pdo_mysql

# Copy file backend yang tidak diabaikan oleh .dockerignore.
COPY . .

# Railway memberikan port lewat environment variable PORT.
# Default 8080 dipakai agar tetap bisa dites lokal dengan Docker.
EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
