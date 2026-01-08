# 🔗 URL Correcta para el Webhook

## ❌ URL Incorrecta:
```
https://bahiapadel2/padelbb.com/deploy-webhook
```

## ✅ URL Correcta:

### Opción A: Ruta Laravel (Recomendado)
```
https://padelbb.com/deploy-webhook
```

### Opción B: Archivo PHP Directo
```
https://padelbb.com/deploy-webhook.php
```

## 📋 Configuración en GitHub:

1. Ve a: `https://github.com/rodri89/bahiapadel/settings/hooks`
2. Haz clic en **"Add webhook"**
3. Configura:
   - **Payload URL**: `https://padelbb.com/deploy-webhook`
   - **Content type**: `application/json`
   - **Secret**: `0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296`
   - **Which events**: "Just the push event"
   - **Active**: ✅ Marcado
4. Haz clic en **"Add webhook"**

## 🔍 Verificar que la URL Funciona:

### Desde el navegador (solo para verificar que existe):
- Deberías ver un error 405 (Method Not Allowed) porque solo acepta POST
- Esto confirma que la ruta existe

### Desde la terminal (prueba real):
```bash
curl -X POST https://padelbb.com/deploy-webhook \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature: sha1=test" \
  -d '{"ref":"refs/heads/main"}'
```

## ⚠️ Nota Importante:

- El dominio es: **`padelbb.com`** (no `bahiapadel2`)
- `bahiapadel2` es solo el nombre de la carpeta en el servidor
- La URL pública siempre usa el dominio: `padelbb.com`

