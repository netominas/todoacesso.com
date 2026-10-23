<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

use TodoAcesso\Core\Env;

/**
 * Convierte lo que escribe el usuario (dominio o URL) en un dominio canónico:
 * minúsculas, sin protocolo, sin "www.", sin ruta/parámetros/fragmento, validado.
 */
final class DomainNormalizer
{
    /** @var array<string,true>|null */
    private static ?array $tlds = null;

    private const RESERVED_TLDS = ['localhost', 'local', 'internal', 'test', 'example', 'invalid', 'lan', 'home', 'corp', 'onion', 'arpa'];

    /** @return array{ok:bool,domain?:string,error?:string,message?:string} */
    public static function normalize(string $input): array
    {
        $s = trim($input);
        if ($s === '' || strlen($s) > 2048) {
            return self::fail('vacio', 'Escribí el dominio o la dirección del sitio que querés comprobar.');
        }
        // Enlace en formato markdown: [texto](https://sitio.com)
        if (preg_match('/^\[[^\]]*\]\(([^)\s]+)\)$/', $s, $m)) {
            $s = $m[1];
        }
        if (preg_match('/\s/u', $s)) {
            return self::fail('espacios', 'La dirección no puede tener espacios. Ejemplo: mercadolibre.com.ar');
        }
        if (preg_match('#^([a-z][a-z0-9+.\-]*)://#i', $s, $m)) {
            if (!in_array(strtolower($m[1]), ['http', 'https'], true)) {
                return self::fail('protocolo', 'Solo aceptamos direcciones http o https.');
            }
        } elseif (preg_match('/^[a-z][a-z0-9+.\-]*:(?!\d+(?:[\/?#]|$))/i', $s)) {
            return self::fail('protocolo', 'Solo aceptamos direcciones http o https.');
        } else {
            $s = 'http://' . ltrim($s, '/');
        }

        $parts = parse_url($s);
        if ($parts === false || empty($parts['host'])) {
            return self::fail('url', 'No pudimos interpretar esa dirección. Probá con algo como instagram.com');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return self::fail('url', 'La dirección no puede incluir usuario ni contraseña.');
        }
        if (isset($parts['port']) && !in_array((int) $parts['port'], [80, 443], true)) {
            return self::fail('puerto', 'Solo podemos verificar sitios en los puertos web estándar.');
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if (str_starts_with($host, '[') || filter_var($host, FILTER_VALIDATE_IP)) {
            return self::fail('ip', 'No verificamos direcciones IP. Escribí el nombre del sitio, por ejemplo google.com');
        }
        if (preg_match('/[^\x20-\x7e]/', $host)) {
            $ascii = function_exists('idn_to_ascii')
                ? idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46)
                : false;
            if ($ascii === false) {
                return self::fail('dominio', 'El dominio tiene caracteres que no podemos procesar.');
            }
            $host = strtolower($ascii);
        }
        if (str_starts_with($host, 'www.') && substr_count($host, '.') >= 2) {
            $host = substr($host, 4);
        }

        $err = self::validateHost($host);
        if ($err !== null) {
            return $err;
        }
        $own = strtolower((string) parse_url(Env::str('APP_URL', ''), PHP_URL_HOST));
        if ($own !== '' && ($host === $own || str_ends_with($host, '.' . $own))) {
            return self::fail('propio', 'Ese es nuestro propio sitio. Probá con otro servicio.');
        }
        return ['ok' => true, 'domain' => $host];
    }

    /** Valida un hostname público (sin quitar www). Devuelve null si es válido. */
    public static function validateHost(string $host, bool $checkTld = true): ?array
    {
        if ($host === '' || strlen($host) > 253) {
            return self::fail('dominio', 'El dominio es demasiado largo o está vacío.');
        }
        $labels = explode('.', $host);
        if (count($labels) < 2) {
            return self::fail('sin_tld', 'Falta la terminación del dominio (.com, .com.ar, etc.). Ejemplo: whatsapp.com');
        }
        foreach ($labels as $label) {
            if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
                return self::fail('dominio', 'El dominio tiene caracteres no válidos.');
            }
            if (substr($label, 2, 2) === '--' && !str_starts_with($label, 'xn--')) {
                return self::fail('dominio', 'El dominio no es válido.');
            }
        }
        $tld = end($labels);
        if (in_array($tld, self::RESERVED_TLDS, true) || ctype_digit($tld)) {
            return self::fail('sin_tld', 'Ese dominio no es público. Probá con un sitio real, por ejemplo mercadopago.com.ar');
        }
        if ($checkTld && !isset(self::tlds()[$tld])) {
            return self::fail('sin_tld', 'No reconocemos la terminación ".' . $tld . '". Revisá que esté bien escrita.');
        }
        return null;
    }

    /** @return array<string,true> */
    private static function tlds(): array
    {
        if (self::$tlds === null) {
            self::$tlds = [];
            $lines = @file(BASE_PATH . '/resources/tlds.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = strtolower(trim($line));
                if ($line !== '' && $line[0] !== '#') {
                    self::$tlds[$line] = true;
                }
            }
        }
        return self::$tlds;
    }

    public static function slugFor(string $domain): string
    {
        return str_replace('.', '-', $domain);
    }

    public static function fail(string $code, string $message): array
    {
        return ['ok' => false, 'error' => $code, 'message' => $message];
    }
}
