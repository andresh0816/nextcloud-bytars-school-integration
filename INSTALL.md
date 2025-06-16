# Instalación y Configuración de Bytars School

## Pasos para instalar la aplicación

### 1. Copia la aplicación al directorio de apps de Nextcloud

```bash
# Copia toda la carpeta bytarsschool-nextcloud al directorio apps de Nextcloud
cp -r bytarsschool-nextcloud /path/to/nextcloud/apps/bytarsschool
```

### 2. Instala las dependencias (si es necesario)

```bash
cd /path/to/nextcloud/apps/bytarsschool
composer install --no-dev
```

### 3. Habilita la aplicación

```bash
cd /path/to/nextcloud
php occ app:enable bytarsschool
```

### 4. Verifica la instalación

```bash
# Ejecuta el script de diagnóstico
php apps/bytarsschool/debug_install.php

# Verifica que la app está habilitada
php occ app:list | grep bytarsschool
```

## Solución de problemas

### La sección no aparece en Seguridad

1. **Verifica que la aplicación esté habilitada:**
   ```bash
   php occ app:enable bytarsschool
   ```

2. **Verifica los logs de Nextcloud:**
   ```bash
   tail -f /path/to/nextcloud/data/nextcloud.log
   ```

3. **Limpiar la caché:**
   ```bash
   php occ maintenance:mode --on
   php occ files:cleanup
   php occ maintenance:mode --off
   ```

4. **Verifica los permisos de archivos:**
   ```bash
   chown -R www-data:www-data /path/to/nextcloud/apps/bytarsschool
   chmod -R 755 /path/to/nextcloud/apps/bytarsschool
   ```

### Errores comunes

1. **Error de namespace o clase no encontrada:**
   - Ejecuta `composer install` en el directorio de la aplicación
   - Verifica que todos los archivos estén presentes

2. **La sección aparece pero no funciona:**
   - Verifica que los archivos JS y CSS estén presentes
   - Revisa la consola del navegador para errores JavaScript

3. **Error 500 al acceder a configuración:**
   - Revisa los logs de PHP y Nextcloud
   - Verifica que todas las clases tengan la sintaxis correcta

## Ubicación de archivos importantes

- Configuración principal: `lib/Settings/Admin.php`
- Registro de aplicación: `lib/AppInfo/Application.php`
- Template de admin: `templates/admin.php`
- Rutas: `appinfo/routes.xml`
- Información de app: `appinfo/info.xml`

## Configuración después de la instalación

1. Ve a **Configuración** → **Administración** → **Seguridad**
2. Busca la sección "Bytars School - Directus Integration"
3. Configura la URL de Directus y el token de administrador
4. Haz clic en "Test Connection" para verificar la conexión
5. Guarda la configuración

## URLs de API importantes

- Guardar configuración: `/apps/bytarsschool/settings/save-admin`
- Probar conexión: `/apps/bytarsschool/settings/test-connection`
