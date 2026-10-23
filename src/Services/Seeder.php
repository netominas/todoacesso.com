<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

/** Carga idempotente de los servicios iniciales. No pisa lo editado a mano. */
final class Seeder
{
    /** @return array{created:int,existing:int} */
    public static function run(): array
    {
        $seed = require BASE_PATH . '/config/services_seed.php';
        $created = 0;
        $existing = 0;
        $order = 0;
        foreach ($seed as $domain => [$name, $category, $description]) {
            $order++;
            $row = ServiceRepository::findByDomain($domain);
            if ($row !== null) {
                $existing++;
                continue;
            }
            ServiceRepository::create($domain, [
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'description_source' => 'seed',
                'featured' => 1,
                'featured_order' => $order * 10,
                'indexable' => 1,
            ]);
            $created++;
        }
        return ['created' => $created, 'existing' => $existing];
    }
}
