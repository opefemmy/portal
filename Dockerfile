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

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies
RUN composer update --optimize-autoloader --no-dev --ignore-platform-req=ext-gd

# Copy everything else
COPY . .

# Create storage directories
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache public/storage public/uploads/passports && \
    chmod -R 777 storage bootstrap/cache public/storage public/uploads

# Create .env with DEBUG enabled for error reporting
RUN cp .env.example .env && \
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=pgsql/' .env && \
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=dpg-d8okllv7f7vs73eseqjg-a/' .env && \
    sed -i 's/DB_PORT=3306/DB_PORT=5432/' .env && \
    sed -i 's/DB_DATABASE=portal/DB_DATABASE=portal_e0lq/' .env && \
    sed -i 's/DB_USERNAME=root/DB_USERNAME=portal_user/' .env && \
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=hjDHRsxzQiXkGYESAAA6EKXUB3gR7HoT/' .env && \
    sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=array/' .env && \
    sed -i 's/CACHE_STORE=database/CACHE_STORE=array/' .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_DEBUG=true" >> .env && \
    echo "LOG_CHANNEL=stderr" >> .env

# Generate key
RUN php artisan key:generate --force

# Expose port
EXPOSE 10000

# Start command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]