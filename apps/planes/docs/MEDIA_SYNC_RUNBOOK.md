# Runbook de sincronización de media pública (`gestionplanes`)

## Objetivo
Recuperar portadas de planes y centros sin borrar datos, copiando solo archivos faltantes al proyecto vigente.

## Comando principal
```bash
cd /Users/pallaresfj/Herd/gestionplanes
bash scripts/sync-public-media.sh
```

Fuentes opcionales:
- Por defecto: `/Users/pallaresfj/Herd/plan/storage/app/public`
- Personalizadas: exportar `MEDIA_SYNC_SOURCE_DIRS` con rutas separadas por `:`

Ejemplo:
```bash
cd /Users/pallaresfj/Herd/gestionplanes
MEDIA_SYNC_SOURCE_DIRS="/Volumes/backup/plan/storage/app/public:/tmp/media" \
  bash scripts/sync-public-media.sh
```

## Qué hace
1. Crea carpetas destino en `storage/app/public`.
2. Copia archivos faltantes desde las rutas configuradas en `MEDIA_SYNC_SOURCE_DIRS`.
3. Crea `public/storage` si no existe.
4. Reporta faltantes reales:
   - `missing_plan_images`
   - `missing_center_images`

## Después de sincronizar
```bash
cd /Users/pallaresfj/Herd/gestionplanes
php artisan optimize:clear
```

## Verificación manual
1. `https://gestionplanes.test/planes`
2. `https://gestionplanes.test/plan/1`
3. `https://gestionplanes.test/centers`
4. `https://gestionplanes.test/center/1`

## Nota de fallback
Si un archivo no existe en ninguna fuente, las vistas usarán imagen institucional:
- Planes: `public/images/planes.jpg`
- Centros: `public/images/centros.jpg`
