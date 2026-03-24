#!/bin/bash

# Wait for database
echo "Esperando a la base de datos..."
until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" 2>/dev/null; do
  sleep 2
done
echo "Base de datos lista."

# Ensure .env file exists
if [ ! -f /var/www/.env ]; then
  echo "Creando archivo .env..."
  cp /var/www/.env.example /var/www/.env
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

# Clear any old cached config and re-cache with current .env values
php artisan config:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "Entrypoint completado. Iniciando servicio..."

# Start CMD
exec "$@"
