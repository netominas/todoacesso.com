<?php
declare(strict_types=1);

namespace TodoAcesso\Core;

use RuntimeException;

final class Security
{
    public static function key(): string
    {
        $key = Env::str('APP_KEY');
        if (strlen($key) < 32) {
            throw new RuntimeException('APP_KEY no está configurada (mínimo 32 caracteres).');
        }
        return $key;
    }

    /**
     * Token firmado para formularios públicos (sin sesión): "ts.firma".
     * Vincula el formulario al navegador (User-Agent) y permite medir el tiempo de llenado.
     */
    public static function publicToken(Request $req): string
    {
        $ts = (string) time();
        return $ts . '.' . substr(hash_hmac('sha256', 'form|' . $ts . '|' . $req->userAgent(), self::key()), 0, 40);
    }

    public static function checkPublicToken(Request $req, string $token, int $minAge = 2, int $maxAge = 14400): bool
    {
        if (!preg_match('/^(\d{9,11})\.([a-f0-9]{40})$/', $token, $m)) {
            return false;
        }
        $expected = substr(hash_hmac('sha256', 'form|' . $m[1] . '|' . $req->userAgent(), self::key()), 0, 40);
        if (!hash_equals($expected, $m[2])) {
            return false;
        }
        $age = time() - (int) $m[1];
        return $age >= $minAge && $age <= $maxAge;
    }

    public static function applyHeaders(Request $req, bool $noindex = false): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()');
        header("Content-Security-Policy: object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        if ($req->isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        if ($noindex) {
            header('X-Robots-Tag: noindex, nofollow');
        }
    }
}
