<?php
declare(strict_types=1);

// Comprueba que los dominios del seed respondan y muestra a dónde redirigen (para confirmar dominios oficiales).
require dirname(__DIR__) . '/src/bootstrap.php';

use TodoAcesso\Services\HttpClient;

$seed = require BASE_PATH . '/config/services_seed.php';
printf("%-28s %-6s %-6s %s\n", 'DOMINIO', 'HTTP', 'MS', 'DESTINO FINAL / ERROR');
foreach ($seed as $domain => [$name]) {
    $r = null;
    foreach (['https://' . $domain . '/', 'https://www.' . $domain . '/'] as $url) {
        $r = HttpClient::get($url, ['timeout' => 8, 'max_bytes' => 1024]);
        if ($r['ok']) {
            break;
        }
    }
    $final = $r['ok'] ? parse_url($r['final_url'], PHP_URL_HOST) . (parse_url($r['final_url'], PHP_URL_PATH) ?? '') : 'ERROR: ' . ($r['error'] ?? '?');
    printf("%-28s %-6s %-6d %s\n", $domain, $r['status'] ?? '-', $r['ttfb_ms'], $final);
}
