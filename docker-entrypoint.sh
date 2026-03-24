#!/bin/bash
set -e

# Wait for database
until pg_isready -h db -p 5432 -U "${DB_USERNAME}"; do
  echo "Waiting for database..."
  sleep 2
done

# Run migrations
php artisan migrate --force

# Storage link
php artisan storage:link

# Start CMD
exec "$@"
