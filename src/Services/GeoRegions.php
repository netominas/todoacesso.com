<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

/**
 * Validación geográfica gruesa (polígono aproximado de Argentina) y asignación de cada reporte
 * a la ciudad de referencia más cercana. En el mapa solo se dibuja el centro de la ciudad,
 * nunca la coordenada del reporte.
 */
final class GeoRegions
{
    /** [lon, lat] — contorno simplificado (precisión de decenas de km). */
    private const POLYGON = [
        [-67.18, -22.82], [-66.2, -22.1], [-65.6, -22.1], [-64.3, -22.0], [-62.65, -22.25],
        [-60.0, -24.0], [-58.2, -24.95], [-57.68, -25.3], [-58.0, -26.9], [-58.6, -27.3],
        [-56.0, -27.4], [-55.2, -26.9], [-54.59, -25.59], [-53.9, -25.9], [-53.65, -26.25],
        [-54.5, -27.2], [-55.1, -27.87], [-56.1, -28.55], [-57.09, -29.7], [-57.6, -30.25],
        [-58.0, -31.4], [-58.3, -32.9], [-58.4, -33.8], [-58.5, -34.4], [-58.35, -34.65],
        [-57.9, -34.9], [-57.2, -35.35], [-56.7, -36.35], [-56.9, -37.1], [-57.25, -38.0],
        [-58.5, -38.6], [-61.1, -39.0], [-61.9, -39.3], [-62.6, -40.8], [-64.6, -40.7],
        [-64.7, -42.8], [-63.4, -42.5], [-65.0, -43.35], [-67.2, -45.9], [-67.2, -46.5],
        [-65.6, -47.75], [-67.4, -49.3], [-68.9, -51.6], [-68.1, -52.4], [-68.4, -52.7],
        [-68.2, -53.0], [-67.3, -53.9], [-66.9, -54.9], [-68.6, -54.9], [-68.6, -52.65],
        [-71.5, -52.0], [-72.3, -51.0], [-73.5, -49.5], [-72.9, -48.0], [-71.9, -46.5],
        [-71.9, -45.5], [-71.7, -44.0], [-71.8, -42.0], [-71.7, -41.1], [-71.5, -40.0],
        [-71.2, -38.0], [-70.4, -36.8], [-70.1, -35.0], [-69.9, -33.5], [-70.0, -32.5],
        [-70.5, -31.0], [-69.9, -29.0], [-68.5, -27.5], [-68.3, -26.5], [-68.5, -25.0],
        [-67.4, -23.0],
    ];

    /** Ciudades de referencia: nombre => [lat, lon]. */
    private const CITIES = [
        'Buenos Aires' => [-34.6037, -58.3816],
        'La Plata' => [-34.9215, -57.9545],
        'Mar del Plata' => [-38.0055, -57.5426],
        'Bahía Blanca' => [-38.7183, -62.2663],
        'Rosario' => [-32.9442, -60.6505],
        'Santa Fe' => [-31.6333, -60.7],
        'Paraná' => [-31.7333, -60.5289],
        'Concordia' => [-31.3929, -58.0209],
        'Córdoba' => [-31.4201, -64.1888],
        'Río Cuarto' => [-33.1232, -64.3493],
        'Corrientes' => [-27.4692, -58.8306],
        'Resistencia' => [-27.4606, -58.9839],
        'Posadas' => [-27.3671, -55.8961],
        'Formosa' => [-26.1775, -58.1781],
        'Salta' => [-24.7821, -65.4232],
        'San Salvador de Jujuy' => [-24.1858, -65.2995],
        'San Miguel de Tucumán' => [-26.8083, -65.2176],
        'Santiago del Estero' => [-27.7951, -64.2615],
        'Catamarca' => [-28.4696, -65.7852],
        'La Rioja' => [-29.4131, -66.8558],
        'San Juan' => [-31.5375, -68.5364],
        'Mendoza' => [-32.8895, -68.8458],
        'San Luis' => [-33.295, -66.3356],
        'Santa Rosa' => [-36.6203, -64.2906],
        'Neuquén' => [-38.9516, -68.0591],
        'Viedma' => [-40.8135, -62.9967],
        'San Carlos de Bariloche' => [-41.1335, -71.3103],
        'Trelew' => [-43.2489, -65.3051],
        'Comodoro Rivadavia' => [-45.8641, -67.4966],
        'Río Gallegos' => [-51.6226, -69.2181],
        'Ushuaia' => [-54.8019, -68.303],
    ];

    public static function insideArgentina(float $lat, float $lon): bool
    {
        if ($lat < -55.2 || $lat > -21.7 || $lon < -73.7 || $lon > -53.5) {
            return false;
        }
        $inside = false;
        $n = count(self::POLYGON);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = self::POLYGON[$i];
            [$xj, $yj] = self::POLYGON[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lon < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-9) + $xi)) {
                $inside = !$inside;
            }
        }
        return $inside;
    }

    public static function nearestCity(float $lat, float $lon): string
    {
        $best = 'Buenos Aires';
        $bestD = INF;
        foreach (self::CITIES as $name => [$clat, $clon]) {
            $d = self::haversine($lat, $lon, $clat, $clon);
            if ($d < $bestD) {
                $bestD = $d;
                $best = $name;
            }
        }
        return $best;
    }

    /** @return array{0:float,1:float}|null */
    public static function cityCenter(string $name): ?array
    {
        return self::CITIES[$name] ?? null;
    }

    private static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $r * asin(min(1.0, sqrt($a)));
    }
}
