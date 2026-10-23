<?php
declare(strict_types=1);

/**
 * Pruebas de integración HTTP: levanta el servidor embebido con una base nueva y recorre los flujos principales.
 * Requiere red (verifica dominios reales). Uso: php tests/http.php
 */
$root = dirname(__DIR__);
$port = 8099;
$base = "http://127.0.0.1:$port";
$db = $root . '/storage/test-http.sqlite';
foreach (['', '-wal', '-shm'] as $s) {
    @unlink($db . $s);
}
$adminPass = 'Prueba-Admin-2026!';
$env = array_merge(getenv(), [
    'APP_ENV' => 'production', 'APP_DEBUG' => '0', 'APP_URL' => 'https://todoacceso.com', 'APP_KEY' => str_repeat('z', 64),
    'APP_AUTO_MIGRATE' => '1', 'DB_PATH' => 'storage/test-http.sqlite', 'GTM_ID' => 'GTM-TEST123',
    'ADMIN_USER' => 'tester', 'ADMIN_PASSWORD_HASH' => password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 8]),
    'REPORT_COOLDOWN_SECONDS' => '120', 'RL_SEARCH_PER_10MIN' => '200',
]);
$proc = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $root . '/public', $root . '/public/index.php'], [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, $root, $env);
register_shutdown_function(static function () use ($proc, $db): void {
    proc_terminate($proc);
    sleep(1);
    foreach (['', '-wal', '-shm'] as $s) {
        @unlink($db . $s);
    }
});
for ($i = 0; $i < 50; $i++) {
    if (@fsockopen('127.0.0.1', $port)) {
        break;
    }
    usleep(100000);
}

$pass = 0;
$fail = 0;
function t(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? '  ok   ' : '  FAIL ') . $name . ($ok ? '' : " $detail") . "\n";
}

$jar = tempnam(sys_get_temp_dir(), 'ta');
/** @return array{code:int,headers:string,body:string,loc:?string} */
function http(string $method, string $path, array $post = [], array $headers = [], ?string $cookieJar = null): array
{
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (TestRunner)', CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = (string) curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $h = substr($raw, 0, $size);
    preg_match('/^Location:\s*(.+)$/mi', $h, $m);
    return ['code' => $code, 'headers' => $h, 'body' => substr($raw, $size), 'loc' => isset($m[1]) ? trim($m[1]) : null];
}

echo "# Home\n";
$r = http('GET', '/');
t('home 200', $r['code'] === 200);
t('lang es-AR', str_contains($r['body'], '<html lang="es-AR">'));
t('canonical absoluta', str_contains($r['body'], '<link rel="canonical" href="https://todoacceso.com/">'));
t('robots index con max-image-preview', str_contains($r['body'], 'content="index,follow,max-image-preview:large"'));
t('og:image y twitter:image', str_contains($r['body'], 'property="og:image"') && str_contains($r['body'], 'name="twitter:image"'));
t('JSON-LD WebSite + SearchAction + Organization', str_contains($r['body'], '"SearchAction"') && str_contains($r['body'], '"Organization"'));
t('GTM script justo tras <head>', (bool) preg_match('/<head>\s*<!-- Google Tag Manager -->\s*<script>/', $r['body']));
t('GTM noscript justo tras <body>', (bool) preg_match('/<body[^>]*>\s*<!-- Google Tag Manager \(noscript\) -->\s*<noscript><iframe src="https:\/\/www\.googletagmanager\.com\/ns\.html\?id=GTM-TEST123"/', $r['body']));
t('encabezado X-Content-Type-Options', stripos($r['headers'], 'X-Content-Type-Options: nosniff') !== false);
t('encabezado X-Frame-Options', stripos($r['headers'], 'X-Frame-Options: DENY') !== false);
t('encabezado Referrer-Policy', stripos($r['headers'], 'Referrer-Policy:') !== false);
t('sin X-Powered-By expuesto o sin stack trace', !str_contains($r['body'], 'Stack trace'));
t('CSS/JS versionados', (bool) preg_match('#/assets/css/app\.css\?v=\d+#', $r['body']) && (bool) preg_match('#/assets/js/app\.js\?v=\d+" defer#', $r['body']));

echo "# Búsqueda y normalización\n";
$variants = ['wikipedia.org', 'https://www.wikipedia.org/', 'http://wikipedia.org/wiki/Argentina?x=1#top', 'WWW.Wikipedia.ORG', 'wikipedia.org/'];
$locs = [];
foreach ($variants as $v) {
    $x = http('GET', '/buscar?q=' . rawurlencode($v));
    $locs[] = $x['loc'];
    t("«{$v}» redirige 303", $x['code'] === 303, "código {$x['code']}");
}
t('todas apuntan a una única página', count(array_unique($locs)) === 1 && $locs[0] === '/estado/wikipedia-org', json_encode($locs));
foreach (['whatsapp', 'localhost', 'http://localhost/x', '127.0.0.1', 'http://192.168.0.1/', 'ftp://wikipedia.org', 'javascript:alert(1)', 'banco', 'sitio.invalidotld', 'x y.com'] as $bad) {
    $x = http('GET', '/buscar?q=' . rawurlencode($bad));
    t("rechaza «{$bad}»", $x['code'] === 422 && str_contains($x['body'], 'alert--error') && $x['loc'] === null, "código {$x['code']}");
}
$x = http('GET', '/buscar?q=' . rawurlencode('dominio-que-no-existe-xyz-123456.com'));
t('dominio inexistente: mensaje y sin registro', $x['code'] === 422 && str_contains($x['body'], 'No encontramos el dominio'));
$x = http('GET', '/buscar');
t('búsqueda vacía → 422', $x['code'] === 422);
t('buscar con XSS se escapa', !str_contains(http('GET', '/buscar?q=' . rawurlencode('"><script>alert(1)</script>.com'))['body'], '<script>alert(1)'));

echo "# Página de servicio\n";
$r = http('GET', '/estado/wikipedia-org');
t('servicio 200', $r['code'] === 200);
t('H1 único', substr_count($r['body'], '<h1') === 1);
t('canonical del servicio', str_contains($r['body'], 'rel="canonical" href="https://todoacceso.com/estado/wikipedia-org"'));
t('servicio nuevo no indexable (noindex,follow)', str_contains($r['body'], 'content="noindex,follow"'));
t('breadcrumb visible + JSON-LD', str_contains($r['body'], 'aria-label="Ruta de navegación"') && str_contains($r['body'], '"BreadcrumbList"'));
t('FAQ visible y FAQPage', str_contains($r['body'], '"FAQPage"') && str_contains($r['body'], '<details>'));
t('botón sitio oficial con rel seguro', str_contains($r['body'], 'href="https://wikipedia.org/" target="_blank" rel="noopener noreferrer nofollow"'));
t('botón verificar de nuevo', str_contains($r['body'], 'data-recheck-form'));
t('aviso de independencia', str_contains($r['body'], 'Aviso de independencia'));
t('mapa y gráfico presentes', str_contains($r['body'], 'id="map"') && str_contains($r['body'], 'class="chart"'));
t('Leaflet NO se carga como <script> en el HTML', !str_contains($r['body'], 'leaflet.js"></script>') && !str_contains($r['body'], 'unpkg'));
t('estado, código HTTP y tiempo visibles', str_contains($r['body'], 'Código HTTP') && str_contains($r['body'], 'Tiempo de respuesta') && str_contains($r['body'], 'Última verificación'));
t('slug en mayúsculas → 301', http('GET', '/estado/Wikipedia-Org')['code'] === 301);
t('barra final → 301', http('GET', '/estado/wikipedia-org/')['code'] === 301);
t('slug inexistente → 404', http('GET', '/estado/no-existe-nada-com')['code'] === 404);
t('slug malicioso → 404', http('GET', '/estado/' . rawurlencode("../../etc/passwd"))['code'] === 404);
$count = (int) (new PDO('sqlite:' . $db))->query("SELECT COUNT(*) FROM services WHERE domain='wikipedia.org'")->fetchColumn();
t('un único registro para todas las variantes', $count === 1);
$sc = (int) (new PDO('sqlite:' . $db))->query("SELECT search_count FROM services WHERE domain='wikipedia.org'")->fetchColumn();
t('contador de búsquedas incrementado (5)', $sc === 5, (string) $sc);

echo "# Reportes\n";
preg_match('/name="_t" value="([^"]+)"/', $r['body'], $m);
$token = $m[1] ?? '';
t('token en el formulario', $token !== '');
$json = ['Accept: application/json', 'X-Requested-With: fetch'];
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'login', '_t' => $token], $json);
t('token muy reciente (<2 s) rechazado', (json_decode($x['body'], true)['status'] ?? '') === 'token', $x['body']);
sleep(3);
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'login', '_t' => 'forjado'], $json);
t('token forjado rechazado', (json_decode($x['body'], true)['status'] ?? '') === 'token');
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'hackeo', '_t' => $token], $json);
t('tipo inválido rechazado en servidor', (json_decode($x['body'], true)['status'] ?? '') === 'invalid', $x['body']);
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'login', '_t' => $token, 'website' => 'spam.com'], $json);
$n0 = (int) (new PDO('sqlite:' . $db))->query('SELECT COUNT(*) FROM reports')->fetchColumn();
t('honeypot: simula éxito sin guardar', (json_decode($x['body'], true)['ok'] ?? false) === true && $n0 === 0);
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'login', '_t' => $token, 'lat' => '-34.61', 'lon' => '-58.38'], $json);
$j = json_decode($x['body'], true);
t('reporte creado + fragmentos actualizados', ($j['status'] ?? '') === 'created' && !empty($j['chart']) && !empty($j['breakdown']) && ($j['total'] ?? 0) === 1, $x['body']);
t('mapa agrega la región, no coordenadas', ($j['regions'][0]['name'] ?? '') === 'Buenos Aires' && !isset($j['regions'][0]['latitude']));
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'login', '_t' => $token], $json);
t('reporte repetido = duplicado', (json_decode($x['body'], true)['status'] ?? '') === 'duplicate');
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'app', '_t' => $token], $json);
t('otro tipo dentro del cooldown', (json_decode($x['body'], true)['status'] ?? '') === 'cooldown');
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'app', '_t' => $token]);
t('sin JavaScript: redirige con mensaje', $x['code'] === 303 && str_contains((string) $x['loc'], '?r=cooldown#reportar'));
$row = (new PDO('sqlite:' . $db))->query('SELECT latitude, longitude, region FROM reports LIMIT 1')->fetch(PDO::FETCH_ASSOC);
t('coordenadas guardadas redondeadas', $row && (float) $row['latitude'] === -34.6 && (float) $row['longitude'] === -58.4, json_encode($row));
$x = http('POST', '/estado/wikipedia-org/reportar', ['problem' => 'pagos', '_t' => $token, 'lat' => '40.4', 'lon' => '-3.7'], ['Accept: application/json', 'X-Requested-With: fetch', 'User-Agent: Mozilla/5.0 (OtroDispositivo)']);
$x2 = http('GET', '/estado/wikipedia-org/datos');
t('endpoint de datos JSON', ($j2 = json_decode($x2['body'], true)) && isset($j2['chart'], $j2['status']));
t('GET sobre ruta de reportes → 405', http('GET', '/estado/wikipedia-org/reportar')['code'] === 405);
$x = http('POST', '/estado/wikipedia-org/verificar', ['_t' => 'malo'], $json);
t('recheck con token inválido rechazado', ($x['code'] === 400));

echo "# Páginas, sitemap y robots\n";
foreach (['/servicios-populares', '/sobre-nosotros', '/privacidad', '/terminos'] as $p) {
    $x = http('GET', $p);
    t("$p 200 + canonical + H1", $x['code'] === 200 && str_contains($x['body'], 'rel="canonical"') && substr_count($x['body'], '<h1') === 1);
}
$x = http('GET', '/privacidad');
t('privacidad cubre ubicación, hash, GTM, OSM y retención', str_contains($x['body'], 'hash anónimo') && str_contains($x['body'], 'Google Tag Manager') && str_contains($x['body'], 'OpenStreetMap') && str_contains($x['body'], 'Cuánto tiempo'));
$x = http('GET', '/sitemap.xml');
$xml = @simplexml_load_string($x['body']);
t('sitemap.xml válido', $xml !== false && $x['code'] === 200 && str_contains($x['headers'], 'application/xml'));
t('sitemap incluye home, populares, legales', str_contains($x['body'], '<loc>https://todoacceso.com/</loc>') && str_contains($x['body'], '/servicios-populares') && str_contains($x['body'], '/privacidad') && str_contains($x['body'], '/terminos') && str_contains($x['body'], '/sobre-nosotros'));
t('sitemap excluye servicios no indexables', !str_contains($x['body'], 'wikipedia-org'));
t('sitemap sin admin ni búsquedas', !str_contains($x['body'], '/admin') && !str_contains($x['body'], '/buscar'));
$x = http('GET', '/robots.txt');
t('robots.txt: bloquea /admin y declara sitemap', str_contains($x['body'], 'Disallow: /admin') && str_contains($x['body'], 'Sitemap: https://todoacceso.com/sitemap.xml'));
$pop = http('GET', '/servicios-populares');
t('ranking no lista servicios no indexables', !str_contains($pop['body'], 'wikipedia'));
t('404 con página de error y noindex', ($y = http('GET', '/no-existe'))['code'] === 404 && str_contains($y['body'], 'noindex'));
t('archivos sensibles no se sirven', http('GET', '/.env')['code'] === 404 && http('GET', '/storage/test-http.sqlite')['code'] === 404 && http('GET', '/src/bootstrap.php')['code'] === 404);

echo "# Administración\n";
$x = http('GET', '/admin', [], [], $jar);
t('/admin sin sesión → login', $x['code'] === 303 && str_contains((string) $x['loc'], '/admin/login'));
$x = http('GET', '/admin/login', [], [], $jar);
t('login noindex,nofollow + X-Robots-Tag', str_contains($x['body'], 'content="noindex,nofollow"') && stripos($x['headers'], 'X-Robots-Tag: noindex, nofollow') !== false);
t('login sin GTM', !str_contains($x['body'], 'googletagmanager'));
$x = http('POST', '/admin/login', ['user' => 'tester', 'password' => 'incorrecta'], [], $jar);
t('credenciales incorrectas → 401', $x['code'] === 401);
$x = http('POST', '/admin/login', ['user' => 'tester', 'password' => $adminPass], [], $jar);
t('login correcto → redirige al panel', $x['code'] === 303 && $x['loc'] === '/admin');
preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $x['headers'], $cm);
$cookie = implode(' | ', $cm[1]);
t('cookie HttpOnly', stripos($cookie, 'HttpOnly') !== false, $cookie);
t('cookie SameSite=Strict', stripos($cookie, 'SameSite=Strict') !== false, $cookie);
t('cookie limitada a /admin', stripos($cookie, 'path=/admin') !== false, $cookie);
$panel = http('GET', '/admin', [], [], $jar);
t('panel accesible', $panel['code'] === 200 && str_contains($panel['body'], 'Pendientes de revisión'));
t('wikipedia aparece como pendiente (no indexado)', str_contains($panel['body'], 'wikipedia.org'));
preg_match('/name="_csrf" value="([a-f0-9]+)"/', $panel['body'], $m);
$csrf = $m[1] ?? '';
t('token CSRF en formularios', strlen($csrf) === 64);
$id = (int) (new PDO('sqlite:' . $db))->query("SELECT id FROM services WHERE domain='wikipedia.org'")->fetchColumn();
$x = http('POST', "/admin/servicios/$id", ['name' => 'Hackeado', 'indexable' => '1'], [], $jar);
t('POST sin CSRF → 419', $x['code'] === 419);
$x = http('POST', "/admin/servicios/$id", ['_csrf' => str_repeat('0', 64), 'name' => 'Hackeado'], [], $jar);
t('POST con CSRF falso → 419', $x['code'] === 419);
$x = http('POST', "/admin/servicios/$id", ['_csrf' => $csrf, 'name' => 'Wikipedia', 'category' => 'sitio', 'description' => 'Enciclopedia libre <b>colaborativa</b>.', 'indexable' => '1', 'featured' => '1', 'featured_order' => '5', 'editorial' => "Primer párrafo editorial.\n\nSegundo párrafo editorial."], [], $jar);
t('guardar con CSRF correcto', $x['code'] === 303);
$page = http('GET', '/estado/wikipedia-org');
t('nombre y descripción manual publicados y escapados', str_contains($page['body'], 'Enciclopedia libre &lt;b&gt;colaborativa&lt;/b&gt;.') && !str_contains($page['body'], '<b>colaborativa'));
t('contenido editorial propio reemplaza el "qué es"', str_contains($page['body'], 'Primer párrafo editorial.') && str_contains($page['body'], 'Segundo párrafo editorial.'));
t('al activar indexación pasa a index,follow', str_contains($page['body'], 'content="index,follow,max-image-preview:large"'));
$sm = http('GET', '/sitemap.xml');
t('y entra en el sitemap', str_contains($sm['body'], '/estado/wikipedia-org'));
t('y en el ranking y la home', str_contains(http('GET', '/servicios-populares')['body'], 'Wikipedia') && str_contains(http('GET', '/')['body'], 'Wikipedia'));
$x = http('POST', "/admin/servicios/$id/accion", ['_csrf' => $csrf, 'do' => 'recheck'], [], $jar);
t('acción verificar ahora', $x['code'] === 303);
$x = http('GET', '/admin/logout', [], [], $jar);
t('logout por GET no permitido', $x['code'] === 405);
$x = http('POST', '/admin/logout', ['_csrf' => $csrf], [], $jar);
t('logout por POST', $x['code'] === 303);
t('tras logout /admin pide login', http('GET', '/admin', [], [], $jar)['code'] === 303);
$jar2 = tempnam(sys_get_temp_dir(), 'ta2');
http('GET', '/admin/login', [], [], $jar2);
$codes = [];
for ($i = 0; $i < 7; $i++) {
    $codes[] = http('POST', '/admin/login', ['user' => 'tester', 'password' => 'mala' . $i], [], $jar2)['code'];
}
$x = http('POST', '/admin/login', ['user' => 'tester', 'password' => $adminPass], [], $jar2);
t('fuerza bruta: bloqueo tras 5 intentos (aun con clave correcta)', $x['code'] === 401 && str_contains($x['body'], 'Demasiados intentos'), implode(',', $codes));

echo "\nResultado: $pass correctas, $fail fallidas\n";
@unlink($jar);
@unlink($jar2);
exit($fail > 0 ? 1 : 0);
