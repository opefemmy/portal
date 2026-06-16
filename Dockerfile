FROM php:8.3-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmariadb-dev-compat \
    libmariadb-dev \
    unzip \
    git \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy only essential files first for layer caching
COPY composer.json composer.lock ./

# Create fresh .env with PostgreSQL settings
RUN echo "APP_ENV=production" > .env && \
    echo "APP_DEBUG=true" >> .env && \
    echo "APP_URL=https://portal.onrender.com" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=dpg-d8okllv7f7vs73eseqjg-a" >> .env && \
    echo "DB_PORT=5432" >> .env && \
    echo "DB_DATABASE=portal_e0lq" >> .env && \
    echo "DB_USERNAME=portal_user" >> .env && \
    echo "DB_PASSWORD=hjDHRsxzQiXkGYESAAA6EKXUB3gR7HoT" >> .env && \
    echo "SESSION_DRIVER=array" >> .env && \
    echo "SESSION_ENCRYPT=false" >> .env && \
    echo "SESSION_PATH=/" >> .env && \
    echo "CACHE_STORE=array" >> .env && \
    echo "QUEUE_CONNECTION=sync" >> .env && \
    echo "LOG_CHANNEL=stderr" >> .env && \
    echo "LOG_LEVEL=debug" >> .env

# Copy all files
COPY . .

# Create storage directories
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache public/storage && \
    chmod -R 777 storage bootstrap/cache public/storage

# Install PHP dependencies
RUN composer update --optimize-autoloader --no-dev --ignore-platform-req=ext-gd

# Generate key
RUN php artisan key:generate --force

# Expose port
EXPOSE 10000

# Start command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]