# 🔗 Configuración de Webhook para Despliegue Automático

## 📋 Paso 1: Generar Clave Secreta

En tu máquina local o servidor, genera una clave secreta:

```bash
openssl rand -hex 32
```

**Copia la clave generada** - la necesitarás en los siguientes pasos.

## 📝 Paso 2: Configurar el Webhook

### En el Servidor (Hostinger):

1. **Edita el archivo `public/deploy-webhook.php`**
2. **Reemplaza** `'CAMBIAR_POR_CLAVE_SECRETA_GENERADA'` con la clave que generaste
3. **Verifica** que la ruta del proyecto sea correcta:
   ```php
   $project_path = '/home/u895805914/domains/padelbb.com/public_html/bahiapadel2';
   ```

### Ejemplo:

```php
$secret = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6'; // Tu clave generada
```

## 🔒 Paso 3: Configurar el Webhook en GitHub

1. Ve a tu repositorio en GitHub: `https://github.com/rodri89/bahiapadel`
2. Ve a **Settings** → **Webhooks** → **Add webhook**
3. Configura:
   - **Payload URL**: `https://padelbb.com/deploy-webhook.php`
   - **Content type**: `application/json`
   - **Secret**: (Pega la clave que generaste)
   - **Which events**: Selecciona **"Just the push event"** o **"Let me select individual events"** → Marca solo **"Pushes"**
   - **Active**: ✅ Marcado
4. Haz clic en **"Add webhook"**

## 🧪 Paso 4: Probar el Webhook

### Opción A: Desde GitHub (Automático)

1. Haz un cambio pequeño en tu código
2. Haz commit y push:
   ```bash
   git add .
   git commit -m "Test webhook"
   git push origin main
   ```
3. En GitHub, ve a **Settings** → **Webhooks** → Haz clic en tu webhook
4. Revisa los **"Recent Deliveries"** para ver si se ejecutó correctamente

### Opción B: Prueba Manual (cURL)

```bash
# Desde tu máquina local o servidor
curl -X POST https://padelbb.com/deploy-webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature: sha1=TU_FIRMA" \
  -d '{"ref":"refs/heads/main"}'
```

### Opción C: Prueba Simple (Sin Seguridad)

Si quieres probar primero sin seguridad, puedes comentar temporalmente la verificación:

```php
// Comentar temporalmente para pruebas
// if ($secret && ...) { ... }
```

**⚠️ IMPORTANTE:** Vuelve a activar la seguridad después de probar.

## 📊 Paso 5: Verificar Logs

Después de que se ejecute el webhook, verifica los logs:

```bash
# En el servidor (SSH)
cd /home/u895805914/domains/padelbb.com/public_html/bahiapadel2
tail -50 storage/logs/webhook-deploy.log
```

## 🔍 Solución de Problemas

### Error 403 (Forbidden)
- Verifica que la clave secreta coincida en GitHub y en el archivo PHP
- Verifica que la firma se esté enviando correctamente

### Error 405 (Method Not Allowed)
- Asegúrate de que GitHub esté enviando POST
- Verifica que el archivo esté en `public/deploy-webhook.php`

### El webhook se ejecuta pero no despliega
- Verifica permisos: `chmod +x deploy.sh`
- Verifica la ruta del proyecto en `deploy-webhook.php`
- Revisa los logs: `tail -f storage/logs/webhook-deploy.log`

### El webhook no se ejecuta
- Verifica que GitHub pueda acceder a la URL
- Revisa los "Recent Deliveries" en GitHub para ver el error
- Verifica que el archivo tenga permisos de lectura

## 📝 Notas de Seguridad

1. **Nunca subas la clave secreta a Git** - Usa variables de entorno o `.env`
2. **Limita el acceso** - Considera agregar IP whitelist si es posible
3. **Monitorea los logs** - Revisa regularmente para detectar intentos de acceso no autorizados
4. **Usa HTTPS** - Asegúrate de que el webhook use HTTPS, no HTTP

## 🎯 Flujo Completo

1. **Haces cambios** en tu código local
2. **Haces commit y push** a GitHub
3. **GitHub detecta el push** y envía POST al webhook
4. **El webhook verifica la firma** (seguridad)
5. **Ejecuta `deploy.sh`** en segundo plano
6. **El sitio se actualiza** automáticamente

¡Listo! 🚀

