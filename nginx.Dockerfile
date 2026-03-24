FROM php:8.4-fpm AS app-source

WORKDIR /var/www

# Copy composer files
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y git zip unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql zip \
    && composer install --no-dev --optimize-autoloader --no-scripts --no-interaction \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy package files and install Node
COPY package.json package-lock.json ./
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm ci

# Copy full app and build assets
COPY . /var/www
RUN composer dump-autoload --optimize && npm run build

# --- Nginx ---
FROM nginx:alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app-source /var/www/public /var/www/public
