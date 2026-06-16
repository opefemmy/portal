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

# Copy application files
COPY . .

# Create all storage directories
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache public/storage && \
    chmod -R 777 storage bootstrap/cache public/storage

# Create fresh .env for production with COOKIE sessions
RUN rm -f .env && \
    cp .env.example .env && \
    php -r "\
\$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);\
\$output = '';\
\$keys = [\
    'APP_ENV' => 'production',\
    'APP_DEBUG' => 'true',\
    'APP_URL' => 'https://portal.onrender.com',\
    'APP_DOMAIN' => '.onrender.com',\
    'DB_CONNECTION' => 'pgsql',\
    'DB_HOST' => 'dpg-d8okllv7f7vs73eseqjg-a',\
    'DB_PORT' => '5432',\
    'DB_DATABASE' => 'portal_e0lq',\
    'DB_USERNAME' => 'portal_user',\
    'DB_PASSWORD' => 'hjDHRsxzQiXkGYESAAA6EKXUB3gR7HoT',\
    'SESSION_DRIVER' => 'cookie',\
    'SESSION_ENCRYPT' => 'false',\
    'SESSION_PATH' => '/',\
    'SESSION_DOMAIN' => '.onrender.com',\
    'SESSION_SAME_SITE' => 'lax',\
    'CACHE_STORE' => 'array',\
    'QUEUE_CONNECTION' => 'sync',\
    'LOG_CHANNEL' => 'stderr',\
    'LOG_LEVEL' => 'debug'\
];\
foreach(\$keys as \$key => \$value) {\
    \$output .= \$key . '=' . \$value . PHP_EOL;\
}\
foreach(\$lines as \$line) {\
    if (strpos(\$line, '=') !== false && !preg_match('/^#/', trim(\$line))) {\
        \$parts = explode('=', \$line, 2);\
        \$k = trim(\$parts[0]);\
        if (!isset(\$keys[\$k])) {\
            \$output .= \$line . PHP_EOL;\
        }\
    }\
}\
file_put_contents('.env', \$output);\
"

# Install PHP dependencies
RUN composer update --optimize-autoloader --no-dev --ignore-platform-req=ext-gd

# Generate key and clear all caches
RUN php artisan key:generate --force && \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan view:clear

# Expose port
EXPOSE 10000

# Start command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]