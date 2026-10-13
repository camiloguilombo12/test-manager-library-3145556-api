#!/bin/sh
set -e

# El esquema es propiedad de las migraciones de la API (ADR-001): se aplican antes de arrancar
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
