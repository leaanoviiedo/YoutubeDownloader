#!/bin/bash
set -e

# Wait for database
until pg_isready -h db -p 5432 -U "${DB_USERNAME}"; do
  echo "Waiting for database..."
  sleep 2
done

# Run migrations
php artisan migrate --force

# Ensure directories exist
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/app/downloads
mkdir -p bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Storage link
php artisan storage:link || true

# Start CMD
exec "$@"
