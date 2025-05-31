#!/bin/sh

# Esperar DB
until php artisan migrate --force; do
  echo "Esperando la base de datos..."
  sleep 3
done

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
