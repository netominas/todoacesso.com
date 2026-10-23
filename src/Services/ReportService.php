<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

use TodoAcesso\Core\Database as DB;
use TodoAcesso\Core\Env;
use TodoAcesso\Core\RateLimiter;
use TodoAcesso\Core\Request;

final class ReportService
{
    /** @return array<string,array{label:string,hint:string}> */
    public static function problemTypes(): array
    {
        static $types = null;
        return $types ??= require BASE_PATH . '/config/problems.php';
    }

    /**
     * @return string created | duplicate | cooldown | rate | invalid
     */
    public static function submit(Request $req, array $service, string $type, ?float $lat, ?float $lon): string
    {
        if (!isset(self::problemTypes()[$type])) {
            return 'invalid';
        }
        if (!RateLimiter::hit('report:' . $req->clientKey(), Env::int('RL_REPORT_PER_HOUR', 20), 3600)) {
            return 'rate';
        }

        $hash = $req->reporterHash();
        $now = time();
        $cooldown = max(10, Env::int('REPORT_COOLDOWN_SECONDS', 120));
        $period = max(60, Env::int('REPORT_PERIOD_SECONDS', 3600));

        $recent = DB::value(
            'SELECT problem_type FROM reports WHERE service_id = ? AND reporter_hash = ? AND created_at > ? ORDER BY created_at DESC LIMIT 1',
            [$service['id'], $hash, $now - $cooldown]
        );
        if ($recent !== null) {
            return $recent === $type ? 'duplicate' : 'cooldown';
        }

        $storedLat = null;
        $storedLon = null;
        $region = null;
        if ($lat !== null && $lon !== null && is_finite($lat) && is_finite($lon) && GeoRegions::insideArgentina($lat, $lon)) {
            $dec = max(0, min(2, Env::int('LOCATION_DECIMALS', 1)));
            $storedLat = round($lat, $dec);
            $storedLon = round($lon, $dec);
            $region = GeoRegions::nearestCity($storedLat, $storedLon);
        }

        try {
            DB::run(
                'INSERT INTO reports (service_id, problem_type, reporter_hash, period_key, latitude, longitude, region, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$service['id'], $type, $hash, intdiv($now, $period), $storedLat, $storedLon, $region, $now]
            );
        } catch (\PDOException $e) {
            if (DB::isUniqueViolation($e)) {
                return 'duplicate';
            }
            throw $e;
        }
        return 'created';
    }

    public static function prune(): int
    {
        $days = max(7, Env::int('REPORT_RETENTION_DAYS', 90));
        return DB::run('DELETE FROM reports WHERE created_at < ?', [time() - $days * 86400]);
    }

    public static function parseCoordinate(string $value, float $min, float $max): ?float
    {
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        $f = (float) $value;
        return ($f >= $min && $f <= $max) ? $f : null;
    }
}
