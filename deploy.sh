#!/bin/bash
# Script de despliegue para Hostinger
# Ejecutar después de cada git pull

echo "🚀 Iniciando despliegue..."

# Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader --no-interaction

# Limpiar cachés
echo "🧹 Limpiando cachés..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Regenerar cachés
echo "⚡ Regenerando cachés..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "✅ Despliegue completado!"

