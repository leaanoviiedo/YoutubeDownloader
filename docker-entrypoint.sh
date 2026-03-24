#!/bin/bash

# Wait for database
echo "Esperando a la base de datos..."
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" 2>/dev/null; do
  sleep 2
done
echo "Base de datos lista."

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  echo "Generando APP_KEY..."
  php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force || echo "Migraciones fallaron o no hay cambios."

# Ensure directories exist
mkdir -p storage/framework/{sessions,views,cache} 2>/dev/null || true
mkdir -p storage/app/downloads 2>/dev/null || true
mkdir -p storage/logs 2>/dev/null || true
mkdir -p bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Storage link
php artisan storage:link 2>/dev/null || true

# Cache config and views for production
php artisan config:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "Entrypoint completado. Iniciando servicio..."

# Start CMD
exec "$@"
