<?php
declare(strict_types=1);

// Cron: reverifica los servicios destacados (recomendado cada 5-10 minutos) y limpia datos viejos.
// */10 * * * * www-data php /var/www/todoacceso/bin/check-featured.php >> /var/log/todoacceso-cron.log 2>&1
require dirname(__DIR__) . '/src/bootstrap.php';

use TodoAcceso\Core\RateLimiter;
use TodoAcceso\Services\AvailabilityChecker;
use TodoAcceso\Services\ReportService;
use TodoAcceso\Services\ServiceRepository;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$start = microtime(true);
$n = 0;
foreach (ServiceRepository::featured(200) as $service) {
    if (microtime(true) - $start > 240) {
        break; // evita solapar ejecuciones
    }
    $res = AvailabilityChecker::check($service, false, false);
    if ($res['checked']) {
        $n++;
    }
}
$pruned = ReportService::prune();
RateLimiter::prune();
echo gmdate('c') . " verificados=$n reportes_purgados=$pruned\n";
