#!/usr/bin/env bash
# Actualiza Todo Acceso en el servidor. Ejecutar como root (o con sudo) desde /var/www/todoacceso.
set -euo pipefail
APP_DIR="${APP_DIR:-/var/www/todoacceso}"
WEB_USER="${WEB_USER:-www-data}"
cd "$APP_DIR"

git fetch --quiet origin
git reset --hard origin/main

# Dependencias: ninguna (PHP puro). Migraciones idempotentes:
sudo -u "$WEB_USER" php bin/migrate.php

# Permisos: el código es de root (solo lectura para PHP); solo storage/ y uploads/ son escribibles.
chown -R root:"$WEB_USER" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 750 {} \;
find "$APP_DIR" -type f -exec chmod 640 {} \;
chmod 750 bin/*.php deploy/deploy.sh
chown -R "$WEB_USER":"$WEB_USER" storage public/uploads
chmod -R u+rwX,g-rwx storage public/uploads
find public -type d -exec chmod 755 {} \; ; find public -type f -exec chmod 644 {} \;
[ -f .env ] && chown root:"$WEB_USER" .env && chmod 640 .env

systemctl reload php8.2-fpm
echo "Despliegue completo: $(git rev-parse --short HEAD)"
