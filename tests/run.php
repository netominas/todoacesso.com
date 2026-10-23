<?php
declare(strict_types=1);

// Pruebas unitarias sin red ni servidor. Uso: php tests/run.php
putenv('APP_KEY=' . str_repeat('k', 64));
putenv('APP_URL=https://todoacceso.com');
putenv('DB_PATH=storage/test-unit.sqlite');
putenv('APP_ENV=local');
@unlink(dirname(__DIR__) . '/storage/test-unit.sqlite');
require dirname(__DIR__) . '/src/bootstrap.php';

use TodoAcceso\Core\Database as DB;
use TodoAcceso\Core\Migrator;
use TodoAcceso\Core\RateLimiter;
use TodoAcceso\Core\Request;
use TodoAcceso\Core\Security;
use TodoAcceso\Services\ActivityData;
use TodoAcceso\Services\DomainNormalizer;
use TodoAcceso\Services\GeoRegions;
use TodoAcceso\Services\HttpClient;
use TodoAcceso\Services\ReportService;
use TodoAcceso\Services\Seo;
use TodoAcceso\Services\ServiceRepository;
use TodoAcceso\Services\SsrfGuard;

$pass = 0;
$fail = 0;
function t(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  ok   $name\n";
    } else {
        $fail++;
        echo "  FAIL $name $detail\n";
    }
}

echo "# Normalización de dominios\n";
$accept = [
    'whatsapp.com' => 'whatsapp.com',
    'WhatsApp.COM' => 'whatsapp.com',
    'instagram.com' => 'instagram.com',
    'mercadolibre.com.ar' => 'mercadolibre.com.ar',
    'https://www.bna.com.ar' => 'bna.com.ar',
    '[www.claro.com.ar](https://www.claro.com.ar)' => 'claro.com.ar',
    'https://www.instagram.com/' => 'instagram.com',
    'http://instagram.com/login?x=1#a' => 'instagram.com',
    'www.instagram.com/' => 'instagram.com',
    'https://instagram.com:443/' => 'instagram.com',
    'mi.argentina.gob.ar' => 'mi.argentina.gob.ar',
    '  chatgpt.com  ' => 'chatgpt.com',
    'bücher.de' => 'xn--bcher-kva.de',
];
foreach ($accept as $in => $out) {
    $r = DomainNormalizer::normalize((string) $in);
    t("acepta «{$in}»",$r['ok'] && $r['domain'] === $out, json_encode($r));
}
$reject = [
    'whatsapp', 'instagram', 'banco', 'localhost', 'http://localhost/', '127.0.0.1', 'http://192.168.1.1/admin', '10.0.0.5',
    '[::1]', 'http://[::1]/', 'ftp://sitio.com', 'javascript:alert(1)', 'mailto:a@b.com', 'file:///etc/passwd', 'foo.local',
    'sitio.internal', 'example.test', 'a b.com', '', 'http://', 'user:pass@sitio.com', 'http://sitio.com:8080/', '.com', 'foo..com',
    '-abc.com', 'abc-.com', 'sitio.cómo-no', 'todoacceso.com', 'www.todoacceso.com', '2130706433', 'sitio.123',
    str_repeat('a', 64) . '.com',
];
foreach ($reject as $in) {
    $r = DomainNormalizer::normalize($in);
    t('rechaza «' . substr($in, 0, 40) . '»', !$r['ok'], json_encode($r));
}
t('slug', DomainNormalizer::slugFor('mercadolibre.com.ar') === 'mercadolibre-com-ar');

echo "# SSRF: IPs\n";
foreach (['127.0.0.1', '10.1.2.3', '172.16.0.1', '172.31.255.255', '192.168.0.1', '169.254.169.254', '100.64.0.1', '0.0.0.0', '224.0.0.1', '255.255.255.255',
    '::1', '::', 'fe80::1', 'fc00::1', 'fd12:3456::1', '::ffff:127.0.0.1', '::ffff:10.0.0.1', '64:ff9b::7f00:1', '2002:7f00:1::1', '2001:db8::1', 'ff02::1', 'no-es-ip'] as $ip) {
    t("bloquea $ip", !SsrfGuard::isPublicIp($ip));
}
foreach (['8.8.8.8', '1.1.1.1', '142.250.78.14', '172.32.0.1', '2a00:1450:4005:80b::200e', '::ffff:8.8.8.8'] as $ip) {
    t("permite $ip", SsrfGuard::isPublicIp($ip));
}
$r = HttpClient::get('http://127.0.0.1/', ['timeout' => 2]);
t('HttpClient bloquea 127.0.0.1', !$r['ok'] && $r['error'] === 'blocked');
$r = HttpClient::get('http://localhost/', ['timeout' => 2]);
t('HttpClient bloquea localhost', !$r['ok'] && $r['error'] === 'blocked');
$r = HttpClient::get('http://169.254.169.254/latest/meta-data/', ['timeout' => 2]);
t('HttpClient bloquea metadata cloud', !$r['ok'] && $r['error'] === 'blocked');
$r = HttpClient::get('ftp://sitio.com/', ['timeout' => 2]);
t('HttpClient bloquea ftp', !$r['ok'] && $r['error'] === 'blocked');
t('resolveUrl relativa', HttpClient::resolveUrl('https://a.com/x/y', '/z') === 'https://a.com/z');
t('resolveUrl esquema peligroso', HttpClient::resolveUrl('https://a.com/', 'javascript:alert(1)') === null);

echo "# Geografía\n";
$inside = ['Buenos Aires' => [-34.6, -58.4], 'Córdoba' => [-31.4, -64.2], 'Salta' => [-24.8, -65.4], 'Ushuaia' => [-54.8, -68.3], 'Bariloche' => [-41.1, -71.3],
    'Mendoza' => [-32.9, -68.8], 'Posadas' => [-27.4, -55.9], 'Rosario' => [-32.9, -60.7], 'Comodoro' => [-45.9, -67.5], 'Jujuy' => [-24.2, -65.3],
    'Río Gallegos' => [-51.6, -69.2], 'Mar del Plata' => [-38.0, -57.5], 'Corrientes' => [-27.5, -58.8], 'Formosa' => [-26.2, -58.2], 'Neuquén' => [-38.95, -68.06]];
foreach ($inside as $n => [$la, $lo]) {
    t("dentro: $n", GeoRegions::insideArgentina($la, $lo));
}
$outside = ['Montevideo' => [-34.9, -56.2], 'Santiago de Chile' => [-33.45, -70.65], 'Asunción' => [-25.29, -57.63], 'São Paulo' => [-23.55, -46.63],
    'Lima' => [-12.05, -77.04], 'La Paz' => [-16.5, -68.15], 'Océano' => [-45, -50], 'Madrid' => [40.4, -3.7], 'Nulo' => [0.0, 0.0], 'Punta del Este' => [-34.95, -54.95],
    'Colonia' => [-34.47, -57.84], 'Puerto Montt' => [-41.47, -72.94]];
foreach ($outside as $n => [$la, $lo]) {
    t("fuera: $n", !GeoRegions::insideArgentina($la, $lo));
}
t('ciudad más cercana', GeoRegions::nearestCity(-34.7, -58.5) === 'Buenos Aires');

echo "# Base de datos y migraciones\n";
$pdo = DB::pdo();
$first = Migrator::run($pdo);
$second = Migrator::run($pdo);
t('migración idempotente', $second === 0, "primera=$first segunda=$second");
t('tablas creadas', (int) DB::value("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('services','reports','app_meta','rate_limits')") === 4);
t('foreign keys activas', (int) DB::value('PRAGMA foreign_keys') === 1);

$svc = ServiceRepository::create('example-prueba.com', ['name' => 'Prueba']);
t('crea servicio', $svc['slug'] === 'example-prueba-com' && (int) $svc['indexable'] === 0);
$dupe = false;
try {
    DB::run('INSERT INTO services (domain, slug, name, created_at, updated_at) VALUES (?,?,?,?,?)', ['example-prueba.com', 'otro', 'X', 1, 1]);
} catch (PDOException $e) {
    $dupe = DB::isUniqueViolation($e);
}
t('dominio único (sin duplicados)', $dupe);
$svc2 = ServiceRepository::create('example.prueba.com', ['name' => 'Otra']);
t('colisión de slug se resuelve', $svc2['slug'] === 'example-prueba-com-2', $svc2['slug']);

echo "# Reportes\n";
$mk = static function (string $ip, string $ua = 'Mozilla/5.0 Test'): Request {
    return new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/', 'REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua]);
};
$reqA = $mk('203.0.113.10');
t('tipo inválido', ReportService::submit($reqA, $svc, 'hackeo', null, null) === 'invalid');
t('reporte creado', ReportService::submit($reqA, $svc, 'login', null, null) === 'created');
t('mismo tipo = duplicado', ReportService::submit($reqA, $svc, 'login', null, null) === 'duplicate');
t('otro tipo en cooldown', ReportService::submit($reqA, $svc, 'app', null, null) === 'cooldown');
$reqB = $mk('203.0.113.11');
t('otro origen puede reportar', ReportService::submit($reqB, $svc, 'login', -34.6037, -58.3816) === 'created');
$row = DB::one('SELECT * FROM reports WHERE latitude IS NOT NULL');
t('coordenadas redondeadas a 1 decimal', $row && $row['latitude'] === -34.6 && $row['longitude'] === -58.4, json_encode($row));
t('región asignada', $row && $row['region'] === 'Buenos Aires');
$reqC = $mk('203.0.113.12');
ReportService::submit($reqC, $svc, 'pagos', 40.4, -3.7);
$row = DB::one("SELECT * FROM reports WHERE problem_type='pagos'");
t('ubicación fuera de Argentina se descarta, reporte se guarda', $row && $row['latitude'] === null && $row['region'] === null);
t('no se guarda IP cruda', (int) DB::value("SELECT COUNT(*) FROM reports WHERE reporter_hash LIKE '%203.0.113%'") === 0);
t('hash de 64 hex', (bool) preg_match('/^[a-f0-9]{64}$/', $reqA->reporterHash()));
t('hash distinto por IP', $reqA->reporterHash() !== $reqB->reporterHash());
t('hash estable', $reqA->reporterHash() === $mk('203.0.113.10')->reporterHash());
$v6a = $mk('2001:db8:aaaa:bbbb::1');
$v6b = $mk('2001:db8:aaaa:bbbb:ffff::2');
t('IPv6: misma /64 = mismo origen', $v6a->reporterHash() === $v6b->reporterHash());

echo "# Rate limiting\n";
$ok = 0;
for ($i = 0; $i < 7; $i++) {
    $ok += RateLimiter::hit('test:rl', 5, 60) ? 1 : 0;
}
t('limita a 5 por ventana', $ok === 5, "permitidos=$ok");
t('exceeded() detecta límite', RateLimiter::exceeded('test:rl', 5, 60));
RateLimiter::clear('test:rl');
t('clear() reinicia', !RateLimiter::exceeded('test:rl', 5, 60));

echo "# Actividad (gráfico)\n";
$a = ActivityData::forService((int) $svc['id']);
t('48 intervalos de 30 min', count($a['slots']) === 48 && $a['slots'][1]['start'] - $a['slots'][0]['start'] === 1800);
t('total 24h = 3', $a['total'] === 3, (string) $a['total']);
t('distribución suma 100%±1', abs(array_sum(array_column($a['distribution'], 'pct')) - 100) <= 2);
t('regiones agregadas', count($a['regions']) === 1 && $a['regions'][0]['name'] === 'Buenos Aires');
t('actividad normal con pocos reportes', $a['level'] === 'normal');
$now = time();
for ($i = 0; $i < 8; $i++) {
    DB::run('INSERT INTO reports (service_id, problem_type, reporter_hash, period_key, created_at) VALUES (?,?,?,?,?)', [$svc['id'], 'conexion', hash('sha256', "burst$i"), 1, $now - 60 * $i]);
}
$a = ActivityData::forService((int) $svc['id']);
t('pico de reportes = actividad elevada', $a['level'] === 'high', json_encode([$a['recent'], $a['baseline_recent']]));
// Línea base: reportes en los 7 días anteriores a la misma hora suben el umbral
$svc3 = ServiceRepository::create('base-habitual.com');
for ($d = 1; $d <= 7; $d++) {
    for ($i = 0; $i < 6; $i++) {
        DB::run('INSERT INTO reports (service_id, problem_type, reporter_hash, period_key, created_at) VALUES (?,?,?,?,?)', [$svc3['id'], 'conexion', hash('sha256', "b$d$i"), $d * 100 + $i, $now - $d * 86400 - $i * 60]);
    }
}
for ($i = 0; $i < 7; $i++) {
    DB::run('INSERT INTO reports (service_id, problem_type, reporter_hash, period_key, created_at) VALUES (?,?,?,?,?)', [$svc3['id'], 'conexion', hash('sha256', "now$i"), 5000 + $i, $now - $i * 60]);
}
$b = ActivityData::forService((int) $svc3['id']);
t('línea base calculada > 0', $b['baseline_recent'] > 0, (string) $b['baseline_recent']);
t('7 reportes con base habitual alta = normal', $b['level'] === 'normal', json_encode([$b['recent'], $b['baseline_recent']]));

echo "# Cascada\n";
ServiceRepository::delete((int) $svc['id']);
t('borrar servicio elimina sus reportes', (int) DB::value('SELECT COUNT(*) FROM reports WHERE service_id = ?', [$svc['id']]) === 0);

echo "# Tokens públicos\n";
$req = $mk('198.51.100.1');
$tok = Security::publicToken($req);
t('token recién emitido es demasiado nuevo para enviar', !Security::checkPublicToken($req, $tok, 2));
t('token válido con minAge 0', Security::checkPublicToken($req, $tok, 0));
t('token manipulado falla', !Security::checkPublicToken($req, substr($tok, 0, -1) . '0', 0));
t('token de otro navegador falla', !Security::checkPublicToken($mk('198.51.100.1', 'Otro UA'), $tok, 0));
$old = (time() - 100) . '.' . substr(hash_hmac('sha256', 'form|' . (time() - 100) . '|Mozilla/5.0 Test', str_repeat('k', 64)), 0, 40);
t('token con 100 s de antigüedad es válido', Security::checkPublicToken($req, $old, 2));
t('basura no es token', !Security::checkPublicToken($req, 'abc', 0));

echo "# SEO / GTM\n";
foreach (['GTM-ABC1234' => true, 'GTM-XXXXXXX' => true, 'GTM-' => false, 'gtm-abc123' => false, 'GTM-AB"><script>' => false, '' => false] as $id => $valid) {
    putenv('GTM_ID=' . $id);
    t("GTM «{$id}» " .($valid ? 'válido' : 'inválido'), (Seo::gtmId() !== null) === $valid);
}
putenv('GTM_ID');
t('title con sufijo si cabe', Seo::title('Corto') === 'Corto | Todo Acceso');
t('title sin sufijo si es largo', Seo::title(str_repeat('a', 58)) === str_repeat('a', 58));
$ld = Seo::graph([Seo::faq([['¿Q?', 'A']])]);
t('JSON-LD FAQPage válido', json_decode($ld, true)['@graph'][0]['@type'] === 'FAQPage');
t('JSON-LD escapa </script>', !str_contains(json_ld(['x' => '</script><b>']), '</script>'));
t('e() escapa HTML', e('<a href="x">&\'') === '&lt;a href=&quot;x&quot;&gt;&amp;&#039;');

echo "# Contenido editorial\n";
foreach (ServiceRepository::featured() as $s) {
    // sin servicios en la base de pruebas: se prueba con una ficha sintética
}
foreach (['whatsapp.com' => 'mensajeria', 'bna.com.ar' => 'banco', 'sitio-raro.com' => 'sitio'] as $d => $cat) {
    $c = \TodoAcceso\Content\ServiceContent::build(['domain' => $d, 'name' => 'Nombre', 'category' => $cat, 'editorial' => '']);
    $words = str_word_count(strip_tags(implode(' ', array_merge($c['about'], $c['uses'], $c['interpret'], array_column($c['troubleshooting'], 1), array_column($c['faq'], 1)))));
    t("contenido de $d: título, FAQ y suficiente texto", $c['title'] !== '' && count($c['faq']) >= 4 && $words > 300, "palabras=$words");
}

echo "\nResultado: $pass correctas, $fail fallidas\n";
@unlink(dirname(__DIR__) . '/storage/test-unit.sqlite');
@unlink(dirname(__DIR__) . '/storage/test-unit.sqlite-wal');
@unlink(dirname(__DIR__) . '/storage/test-unit.sqlite-shm');
exit($fail > 0 ? 1 : 0);
