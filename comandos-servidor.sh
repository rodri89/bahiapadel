#!/bin/bash
# Comandos para ejecutar en el servidor vía SSH
# Copia y pega estos comandos en tu conexión SSH

# 1. Limpiar cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 2. Regenerar cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Optimizar (opcional, para producción)
php artisan optimize

# 4. Regenerar autoload de Composer
composer dump-autoload --optimize

echo "✓ Todos los comandos ejecutados correctamente"

