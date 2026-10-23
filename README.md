# Todo Acceso

Portal en PHP para comprobar si sitios, apps, bancos, operadoras y servicios online **funcionan en Argentina**, con reportes anónimos de la comunidad, gráfico de 24 h y mapa aproximado. Interfaz, contenido, metadatos y datos estructurados en **español rioplatense (es-AR)**. Dominio: <https://todoacceso.com>.

> Proyecto independiente: no está afiliado a ninguna de las marcas consultadas. Diseño, textos y código propios (no copia Downdetector ni foradoar.com.br).

## Stack

- PHP 8.2+ (sin Composer ni dependencias; autoloader propio), PDO con *prepared statements*.
- SQLite por defecto; MySQL soportado (`DB_DRIVER=mysql`). Migraciones idempotentes en `database/migrations.php`.
- Nginx + PHP-FPM en producción. CSS y JS propios (sin frameworks). Leaflet 1.9.4 **autoalojado** en `public/assets/vendor/leaflet` + teselas de OpenStreetMap, cargado solo en las fichas y cuando el mapa se acerca a la pantalla.
- Extensiones PHP: `pdo_sqlite` (o `pdo_mysql`), `curl`, `mbstring`, `gd`, `fileinfo`, `intl`, `dom`/`xml`, `openssl`.

## Estructura

```
public/            raíz web (index.php, assets/, uploads/)
src/Core/          Env, Database, Migrator, Router, Request, Response, View, Auth, Security, RateLimiter, Logger
src/Services/      DomainNormalizer, SsrfGuard, HttpClient, AvailabilityChecker, MetadataImporter, ImageImporter,
                   ReportService, ActivityData, GeoRegions, Seo, ServiceRepository, Seeder, Status
src/Content/       Plantillas editoriales por categoría + textos específicos de servicios prioritarios
src/Controllers/   PublicController, PageController (legales/sitemap/robots), AdminController
views/             Plantillas PHP (layout, home, service, popular, pages/, partials/, admin/)
config/            problems.php (tipos de problema), services_seed.php (30 servicios argentinos)
resources/tlds.txt Lista de TLD válidos
bin/               migrate, check-featured (cron), verify-seed, make-key, make-admin-hash, update-tlds, make-brand-assets
deploy/            Nginx, cron, logrotate, deploy.sh
tests/             run.php (unitarias) y http.php (integración)
```

## Instalación local

```bash
cp .env.example .env
php bin/make-key.php                      # pegar en APP_KEY
php bin/make-admin-hash.php --generate    # pegar ADMIN_PASSWORD_HASH (entre comillas simples) y anotar la contraseña
# en .env: APP_ENV=local, APP_DEBUG=1, APP_AUTO_MIGRATE=1, APP_URL=http://127.0.0.1:8080, ADMIN_USER=admin
php bin/migrate.php --seed                # crea la base y carga los 30 servicios
php -S 127.0.0.1:8080 -t public public/index.php
```

Panel: <http://127.0.0.1:8080/admin>. Verificar los dominios del seed: `php bin/verify-seed.php`.
Para traer iconos/portadas y estados reales: `php bin/check-featured.php`.

## Pruebas

```bash
php tests/run.php     # 159 comprobaciones unitarias (normalización, SSRF, geografía, reportes, gráfico, tokens, GTM, SEO)
php tests/http.php    # ~100 comprobaciones de integración con servidor propio y base temporal (requiere internet)
```

## Cómo funciona

- **Búsqueda** (`/buscar?q=`): se normaliza (sin protocolo, `www`, ruta, parámetros ni mayúsculas), se valida (TLD de `resources/tlds.txt`, sin IPs/puertos raros/hosts locales) y se redirige (303) a la ficha canónica `/estado/{dominio-con-guiones}`. Un dominio existente nunca se duplica; solo se incrementa `search_count`. Un dominio nuevo que no resuelve por DNS no se registra.
- **Verificación**: DNS → bloqueo de IPs privadas/reservadas → HTTPS, luego HTTP, luego `www.` → hasta 4 redirecciones validadas salto a salto → código HTTP y tiempo hasta el primer byte. Caché `CHECK_CACHE_SECONDS`. Estados: *Operativo*, *Responde lento*, *El servidor responde* (4xx), *Error del servidor* (5xx), *Sin respuesta*. Se distingue "el servidor respondió" de "funciona perfecto".
- **Reportes**: sin registro. Token firmado + *honeypot* + hash anónimo HMAC(IP/64, UA, `APP_KEY`) + cooldown + índice único (servicio, hash, tipo, período) + rate limit. No se guarda IP.
- **Ubicación**: se pide solo al pulsar “Informar problema”; si se niega, falla o tarda >9 s el reporte sale igual. Se valida contra un polígono aproximado de Argentina, se redondea (`LOCATION_DECIMALS=1`) y el mapa dibuja **solo el centro de la ciudad de referencia** con el total de las últimas 24 h, nunca puntos individuales.
- **Gráfico**: 48 barras de 30 min (SVG renderizado en el servidor), línea punteada con el promedio de los 7 días anteriores en el mismo intervalo, estado “Actividad normal/elevada” (texto + icono, no solo color) y tabla accesible.
- **Imágenes y metadatos**: favicon/apple-touch-icon/`og:image`/`twitter:image` + `meta description`; descarga con validación SSRF, límite de tamaño, MIME real, **re-codificación con GD** (no se ejecuta SVG) y copia local. Lo cargado manualmente nunca se pisa salvo que se pida.

## Panel administrativo

Login con sesión segura (`HttpOnly`, `Secure` en HTTPS, `SameSite=Strict`, ID regenerado, expiración), CSRF en todos los formularios, logout por POST, bloqueo tras 5 intentos fallidos/15 min, `noindex,nofollow`. Gestiona servicios (alta, nombre, descripción, categoría, contenido editorial, destacado y orden, indexación, ícono, portada, reimportar metadatos, verificar ahora, borrar), ver ficha pública, búsquedas y reportes. “Pendientes” lista los dominios buscados por visitantes (no indexados) para aprobarlos.

## Seguridad (resumen)

Prepared statements · escape de toda salida · CSRF y cookies estrictas · SSRF (resolución propia, IPs fijadas con `CURLOPT_RESOLVE`, redirecciones manuales, solo puertos 80/443) · uploads re-codificados · rate limiting en BD y en Nginx · logs sin IP ni formularios · sin *stack traces* en producción · `.env`, base y uploads fuera del control de versiones · Nginx bloquea código/datos y no ejecuta PHP en `/uploads`.

## SEO (resumen)

Title/description/H1/canonical únicos · `robots` correcto (fichas nuevas `noindex,follow` hasta que se aprueban) · Open Graph/Twitter Cards/`max-image-preview:large` · JSON-LD `WebSite`+`SearchAction`, `Organization`, `WebPage`, `BreadcrumbList`, `FAQPage` (solo con FAQ visible) · `/sitemap.xml` con URLs absolutas y `lastmod` (solo páginas canónicas indexables) · `/robots.txt` · contenido editorial por servicio (≈600-760 palabras) · enlaces internos a servicios relacionados · CSS/JS versionados con `?v=`.

## Google Tag Manager

`GTM_ID=GTM-XXXXXXX` en `.env`. Se valida con `^GTM-[A-Z0-9]{4,10}$`; si está vacío o inválido no se renderiza nada. El script va justo después de `<head>` y el `<noscript>` justo después de `<body>` en todas las páginas públicas (el admin no lo usa).

## Publicación en la VPS (Ubuntu/Debian)

1. Paquetes: `apt install nginx php8.2-fpm php8.2-sqlite3 php8.2-curl php8.2-mbstring php8.2-gd php8.2-intl php8.2-xml git certbot`.
2. Clonar en `/var/www/todoacceso` (rama `main`), crear `.env` desde `.env.example` con `APP_ENV=production`, `APP_DEBUG=0`, `APP_AUTO_MIGRATE=0`, **nueva** `APP_KEY`, nueva contraseña admin, `GTM_ID` del contenedor propio.
3. `bash deploy/deploy.sh` (migra, ajusta permisos: código de solo lectura, `storage/` y `public/uploads/` escribibles por `www-data`, `.env` 640). Primera vez: `sudo -u www-data php bin/migrate.php --seed`.
4. Nginx: copiar `deploy/nginx-http-bootstrap.conf`, emitir certificado `certbot certonly --webroot -w /var/www/letsencrypt -d todoacceso.com -d www.todoacceso.com -m EMAIL --agree-tos`, y luego instalar `deploy/nginx-todoacceso.conf` (www → apex, HTTPS, PHP-FPM, caché de estáticos, rate limit).
5. Cron: `deploy/cron-todoacceso` → `/etc/cron.d/todoacceso`; logrotate: `deploy/logrotate-todoacceso`.
6. Validar: `/robots.txt`, `/sitemap.xml`, canonical, GTM, `curl -I https://todoacceso.com`. Enviar el sitemap a Search Console (propiedad propia).

## Notas y límites conocidos

- Algunos WAF descartan User-Agents con “bot” o URL (p. ej. **Santander Argentina**, que hoy figura “Sin respuesta” desde cualquier cliente que se identifique). Se puede cambiar el UA con `CHECK_USER_AGENT`, a criterio del operador.
- `resources/tlds.txt` es una lista curada; completarla con `php bin/update-tlds.php` (descarga la lista oficial de IANA).
- El polígono de Argentina es grueso (decenas de km) y solo sirve para descartar ubicaciones evidentemente fuera del país.
- Para pasar a MySQL: `DB_DRIVER=mysql` + credenciales y `php bin/migrate.php --seed` (el esquema usa marcadores por motor; probado el flujo en SQLite).
- Detrás de Cloudflare u otro proxy, configurar `TRUSTED_PROXIES` y `real_ip` en Nginx para limitar por IP real.
