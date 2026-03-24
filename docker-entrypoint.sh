#!/bin/bash

# Wait for database
echo "Esperando a la base de datos..."
until pg_isready -h db -p 5432 -U "${DB_USERNAME}" 2>/dev/null; do
  sleep 2
done
echo "Base de datos lista."

# Run migrations
php artisan migrate --force || echo "Migraciones fallaron o no hay cambios."

# Ensure directories exist
mkdir -p storage/framework/{sessions,views,cache} 2>/dev/null || true
mkdir -p storage/app/downloads 2>/dev/null || true
mkdir -p bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Storage link
php artisan storage:link 2>/dev/null || true

echo "Entrypoint completado. Iniciando servicio..."

# Start CMD
exec "$@"
