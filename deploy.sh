cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2

# 1. BORRA el deploy.sh viejo
rm deploy.sh

# 2. CREA el nuevo con rutas EXPLÍCITAS de PHP 8.3
cat > deploy.sh << 'EOF'
#!/bin/bash
set -e

echo "🚀 Iniciando despliegue..."

# Cambiar al directorio del proyecto
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2

echo "📥 Actualizando código desde GitHub..."
git pull origin main

# CONFIGURACIÓN EXPLÍCITA PARA HOSTINGER PHP 8.3
PHP_BIN="/opt/alt/php83/usr/bin/php"
COMPOSER_CMD="$PHP_BIN /opt/alt/php83/usr/bin/composer"
ARTISAN_CMD="$PHP_BIN artisan"

echo "📦 Instalando dependencias..."
$COMPOSER_CMD install --no-dev --optimize-autoloader --ignore-platform-req=ext-sodium --no-interaction

echo "🧹 Limpiando cachés..."
$ARTISAN_CMD config:clear
$ARTISAN_CMD cache:clear
$ARTISAN_CMD view:clear
$ARTISAN_CMD route:clear

echo "⚡ Regenerando cachés..."
$ARTISAN_CMD config:cache
$ARTISAN_CMD view:cache

# Intentar cachear rutas (opcional)
echo "🛣️  Cacheando rutas..."
if $ARTISAN_CMD route:cache 2>/dev/null; then
    echo "   ✅ Rutas cacheadas"
else
    echo "   ⚠️  Saltando cache de rutas"
fi

$ARTISAN_CMD optimize

echo "✅ Despliegue completado!"
EOF

# 3. Dale permisos
chmod +x deploy.sh

# 4. Verifica que usa PHP 8.3
head -20 deploy.sh
