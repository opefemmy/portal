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

# Create fresh .env for production
RUN rm -f .env && \
    cp .env.example .env && \
    php -r "\
\$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);\
\$output = '';\
\$keys = [\
    'APP_ENV' => 'production',\
    'APP_DEBUG' => 'false',\
    'APP_URL' => '',\
    'DB_CONNECTION' => 'pgsql',\
    'DB_HOST' => 'dpg-d8o6956gvqtc73fvo8b0-a',\
    'DB_PORT' => '5432',\
    'DB_DATABASE' => 'portal_9oln',\
    'DB_USERNAME' => 'portal_user',\
    'DB_PASSWORD' => 'dJqjXP3yDxHq86ElaA6gSI6IxBDXCsdE',\
    'SESSION_DRIVER' => 'file',\
    'CACHE_STORE' => 'file',\
    'QUEUE_CONNECTION' => 'sync'\
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

# Install PHP dependencies - ignore platform requirements
RUN composer update --optimize-autoloader --no-dev --ignore-platform-req=ext-gd

# THEN generate key
RUN php artisan key:generate

# Expose port
EXPOSE 10000

# Start command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]