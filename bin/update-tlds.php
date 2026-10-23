<?php
declare(strict_types=1);

// Actualiza resources/tlds.txt con la lista oficial de IANA. Ejecutar manualmente (p. ej. cada trimestre).
require dirname(__DIR__) . '/src/bootstrap.php';

$ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'TodoAcesso-tld-updater']]);
$raw = @file_get_contents('https://data.iana.org/TLD/tlds-alpha-by-domain.txt', false, $ctx);
if ($raw === false) {
    fwrite(STDERR, "No se pudo descargar la lista de IANA.\n");
    exit(1);
}
$tlds = [];
foreach (preg_split('/\R/', $raw) as $line) {
    $line = strtolower(trim($line));
    if ($line === '' || $line[0] === '#') {
        continue;
    }
    if (preg_match('/^[a-z0-9-]{2,63}$/', $line)) {
        $tlds[] = $line;
    }
}
if (count($tlds) < 1000) {
    fwrite(STDERR, "La lista descargada parece incompleta (" . count($tlds) . " TLD). No se modificó el archivo.\n");
    exit(1);
}
file_put_contents(BASE_PATH . '/resources/tlds.txt', "# Lista oficial de IANA, actualizada " . gmdate('Y-m-d') . "\n" . implode("\n", $tlds) . "\n");
echo count($tlds) . " TLD guardados.\n";
