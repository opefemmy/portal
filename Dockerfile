FROM php:8.3-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmysqlclient-dev \
    unzip \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libmbstring-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Create .env with production database settings
RUN php -r "\
\$env = file_exists('.env') ? include('.env') : [];\
\$defaults = [\
    'APP_ENV' => 'production',\
    'APP_DEBUG' => 'false',\
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
\$env = array_merge(\$defaults, \$env);\
\$output = '';\
foreach(\$env as \$key => \$value) {\
    \$output .= \$key . '=' . \$value . PHP_EOL;\
}\
file_put_contents('.env', \$output);\
"

# Install PHP dependencies
RUN composer update --optimize-autoloader --no-dev

# Expose port
EXPOSE 10000

# Start command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]