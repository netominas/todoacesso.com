<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

use TodoAcceso\Core\Database as DB;
use TodoAcceso\Core\Env;

/** Datos de las últimas 24 h: serie de 30 minutos, actividad habitual, distribución y regiones. */
final class ActivityData
{
    private const SLOT = 1800;
    private const SLOTS = 48;

    /**
     * @return array{
     *   slots:array<int,array{start:int,count:int,baseline:float}>,total:int,recent:int,baseline_recent:float,
     *   level:string,max:float,distribution:array<int,array{type:string,label:string,count:int,pct:int}>,
     *   regions:array<int,array{name:string,lat:float,lon:float,count:int}>,located:int
     * }
     */
    public static function forService(int $serviceId): array
    {
        $now = time();
        $currentSlot = intdiv($now, self::SLOT);
        $firstSlot = $currentSlot - (self::SLOTS - 1);
        $since = ($firstSlot - 7 * 48) * self::SLOT;

        $div = DB::intDiv('created_at', self::SLOT);
        $rows = DB::all(
            "SELECT $div AS slot, COUNT(*) AS c FROM reports WHERE service_id = ? AND created_at >= ? GROUP BY slot",
            [$serviceId, $since]
        );
        $counts = [];
        foreach ($rows as $r) {
            $counts[(int) $r['slot']] = (int) $r['c'];
        }

        $slots = [];
        $total = 0;
        $max = 1.0;
        for ($i = 0; $i < self::SLOTS; $i++) {
            $slot = $firstSlot + $i;
            $sum = 0;
            for ($d = 1; $d <= 7; $d++) {
                $sum += $counts[$slot - $d * 48] ?? 0;
            }
            $count = $counts[$slot] ?? 0;
            $baseline = round($sum / 7, 2);
            $slots[] = ['start' => $slot * self::SLOT, 'count' => $count, 'baseline' => $baseline];
            $total += $count;
            $max = max($max, (float) $count, $baseline);
        }

        $recentSlots = array_slice($slots, -4);
        $recent = array_sum(array_column($recentSlots, 'count'));
        $baseRecent = round(array_sum(array_column($recentSlots, 'baseline')), 2);
        $minReports = max(1, Env::int('ACTIVITY_MIN_REPORTS', 5));
        $factor = max(1.0, (float) Env::str('ACTIVITY_FACTOR', '2'));
        $level = ($recent >= $minReports && $recent >= $factor * max(1.0, $baseRecent)) ? 'high' : 'normal';

        $since24 = $now - 86400;
        $types = ReportService::problemTypes();
        $dist = [];
        foreach (DB::all('SELECT problem_type, COUNT(*) AS c FROM reports WHERE service_id = ? AND created_at >= ? GROUP BY problem_type ORDER BY c DESC', [$serviceId, $since24]) as $r) {
            if (!isset($types[$r['problem_type']])) {
                continue;
            }
            $dist[] = ['type' => $r['problem_type'], 'label' => $types[$r['problem_type']]['label'], 'count' => (int) $r['c'], 'pct' => 0];
        }
        $distTotal = array_sum(array_column($dist, 'count'));
        foreach ($dist as &$d) {
            $d['pct'] = $distTotal > 0 ? (int) round($d['count'] * 100 / $distTotal) : 0;
        }
        unset($d);

        $regions = [];
        $located = 0;
        foreach (DB::all('SELECT region, COUNT(*) AS c FROM reports WHERE service_id = ? AND created_at >= ? AND region IS NOT NULL GROUP BY region ORDER BY c DESC', [$serviceId, $since24]) as $r) {
            $center = GeoRegions::cityCenter((string) $r['region']);
            if ($center === null) {
                continue;
            }
            $regions[] = ['name' => $r['region'], 'lat' => $center[0], 'lon' => $center[1], 'count' => (int) $r['c']];
            $located += (int) $r['c'];
        }

        return [
            'slots' => $slots, 'total' => $total, 'recent' => $recent, 'baseline_recent' => $baseRecent,
            'level' => $level, 'max' => $max, 'distribution' => $dist, 'regions' => $regions, 'located' => $located,
        ];
    }

    public static function reportCount24h(int $serviceId): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM reports WHERE service_id = ? AND created_at >= ?', [$serviceId, time() - 86400]);
    }
}
