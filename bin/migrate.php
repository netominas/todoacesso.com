<?php
declare(strict_types=1);

// Aplica las migraciones pendientes (idempotente) y carga los servicios iniciales con --seed.
require dirname(__DIR__) . '/src/bootstrap.php';

use TodoAcceso\Core\Database;
use TodoAcceso\Core\Migrator;
use TodoAcceso\Services\Seeder;

$applied = Migrator::run(Database::pdo());
echo "Migraciones aplicadas: $applied\n";
if (in_array('--seed', $argv, true)) {
    $r = Seeder::run();
    echo "Servicios creados: {$r['created']}, ya existentes: {$r['existing']}\n";
}
