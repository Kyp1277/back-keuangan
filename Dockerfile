FROM php:8.2-cli

# ============================================
# Dockerfile untuk UMKM Keuangan REST API
# Deploy ke Render tanpa Apache
# ============================================

WORKDIR /app

# Install ekstensi PDO MySQL untuk koneksi database MySQL eksternal.
RUN docker-php-ext-install pdo pdo_mysql

# Copy file backend yang tidak diabaikan oleh .dockerignore.
COPY . .

# Render memberikan port lewat environment variable PORT.
# Default 10000 mengikuti default Render, tetap bisa dites lokal dengan Docker.
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
