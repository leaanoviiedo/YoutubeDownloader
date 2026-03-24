FROM php:8.4-fpm AS app-build

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    ffmpeg \
    python3 \
    python3-pip \
    postgresql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy package files for npm
COPY package.json package-lock.json ./

# Install JS dependencies
RUN npm ci

# Copy rest of the application
COPY . /var/www

# Create .env from .env.example and generate APP_KEY during build
RUN cp .env.example .env \
    && php artisan key:generate --force

# Run composer scripts after full copy
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Ensure storage and cache directories exist
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/app/downloads \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/.env

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 9000 and start php-fpm server
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
