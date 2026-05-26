#!/bin/sh
set -e

echo "Esperando PostgreSQL en $DB_HOST:$DB_PORT..."
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  sleep 1
done

echo "PostgreSQL disponible. Ejecutando migraciones..."
python manage.py makemigrations --noinput
python manage.py migrate --noinput

echo "Iniciando Django API en 0.0.0.0:8000"
python manage.py runserver 0.0.0.0:8000
