<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

use TodoAcceso\Core\Env;

/**
 * Cliente HTTP saliente seguro: resuelve y valida DNS en cada salto, fija la IP con CURLOPT_RESOLVE
 * (evita DNS rebinding), sigue redirecciones a mano con límite y descarga un máximo de bytes.
 */
final class HttpClient
{
    public static function userAgent(): string
    {
        $custom = trim(\TodoAcceso\Core\Env::str('CHECK_USER_AGENT'));
        if ($custom !== '' && !preg_match('/[\r\n]/', $custom)) {
            return substr($custom, 0, 200);
        }
        return 'TodoAccesoBot/1.0 (+' . app_url('/sobre-nosotros') . ')';
    }

    /**
     * @param array{timeout?:int,connect_timeout?:int,max_bytes?:int,max_redirects?:int,accept?:string,verify_tls?:bool} $opt
     * @return array{ok:bool,status:?int,ttfb_ms:int,final_url:string,body:string,content_type:string,error:?string}
     */
    public static function get(string $url, array $opt = []): array
    {
        $timeout = $opt['timeout'] ?? Env::int('CHECK_TIMEOUT_SECONDS', 6);
        $connectTimeout = $opt['connect_timeout'] ?? min(4, $timeout);
        $maxBytes = $opt['max_bytes'] ?? 4096;
        $maxRedirects = $opt['max_redirects'] ?? Env::int('CHECK_MAX_REDIRECTS', 4);
        $accept = $opt['accept'] ?? 'text/html,application/xhtml+xml,*/*;q=0.8';
        $verify = $opt['verify_tls'] ?? true;

        $result = ['ok' => false, 'status' => null, 'ttfb_ms' => 0, 'final_url' => $url, 'body' => '', 'content_type' => '', 'error' => null];
        $current = $url;
        $ttfbTotal = 0.0;

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            $p = parse_url($current);
            $scheme = strtolower((string) ($p['scheme'] ?? ''));
            $host = strtolower((string) ($p['host'] ?? ''));
            if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
                $result['error'] = 'blocked';
                return $result;
            }
            $port = (int) ($p['port'] ?? ($scheme === 'https' ? 443 : 80));
            if (!in_array($port, [80, 443], true) || isset($p['user']) || isset($p['pass'])) {
                $result['error'] = 'blocked';
                return $result;
            }
            if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) || DomainNormalizer::validateHost($host, false) !== null) {
                $result['error'] = 'blocked';
                return $result;
            }
            $res = SsrfGuard::resolve($host);
            if (!$res['ok']) {
                $result['error'] = $res['error'] ?? 'dns';
                return $result;
            }
            $v4 = array_values(array_filter($res['ips'], static fn ($ip) => !str_contains($ip, ':')));
            $pin = $v4 ?: array_map(static fn ($ip) => '[' . $ip . ']', $res['ips']);

            $headers = [];
            $body = '';
            $truncated = false;
            $ch = curl_init($current);
            curl_setopt_array($ch, [
                CURLOPT_RESOLVE => [$host . ':' . $port . ':' . implode(',', $pin)],
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_USERAGENT => self::userAgent(),
                CURLOPT_HTTPHEADER => ['Accept: ' . $accept, 'Accept-Language: es-AR,es;q=0.9,en;q=0.5'],
                CURLOPT_ENCODING => '',
                CURLOPT_PROXY => '',
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_HEADERFUNCTION => static function ($c, string $line) use (&$headers): int {
                    $len = strlen($line);
                    if (str_starts_with($line, 'HTTP/')) {
                        $headers = [];
                    } elseif (str_contains($line, ':')) {
                        [$k, $v] = explode(':', $line, 2);
                        $headers[strtolower(trim($k))] = trim($v);
                    }
                    return $len;
                },
                CURLOPT_WRITEFUNCTION => static function ($c, string $chunk) use (&$body, &$truncated, $maxBytes): int {
                    $room = $maxBytes - strlen($body);
                    if ($room <= 0) {
                        $truncated = true;
                        return 0;
                    }
                    $body .= substr($chunk, 0, $room);
                    if (strlen($chunk) > $room) {
                        $truncated = true;
                        return 0;
                    }
                    return strlen($chunk);
                },
            ]);
            curl_exec($ch);
            $errno = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $ttfbTotal += (float) curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME);
            curl_close($ch);

            // Corte voluntario por límite de bytes: la respuesta llegó, no es un error.
            if ($errno !== 0 && !($truncated && $errno === CURLE_WRITE_ERROR && $status > 0)) {
                $result['error'] = self::errorCode($errno);
                $result['ttfb_ms'] = (int) round($ttfbTotal * 1000);
                return $result;
            }

            $result['status'] = $status;
            $result['final_url'] = $current;
            $result['content_type'] = strtolower($headers['content-type'] ?? '');
            $result['body'] = $body;
            $result['ttfb_ms'] = (int) round($ttfbTotal * 1000);

            if ($status >= 300 && $status < 400 && !empty($headers['location'])) {
                if ($hop === $maxRedirects) {
                    $result['ok'] = true;
                    $result['error'] = 'redirects';
                    return $result;
                }
                $next = self::resolveUrl($current, $headers['location']);
                if ($next === null) {
                    $result['ok'] = true;
                    return $result;
                }
                $current = $next;
                continue;
            }
            $result['ok'] = true;
            return $result;
        }
        return $result;
    }

    private static function errorCode(int $errno): string
    {
        return match (true) {
            $errno === CURLE_OPERATION_TIMEDOUT => 'timeout',
            $errno === CURLE_COULDNT_RESOLVE_HOST => 'dns',
            $errno === CURLE_COULDNT_CONNECT => 'connect',
            in_array($errno, [35, 51, 53, 54, 58, 59, 60, 64, 66, 77, 80, 82, 83, 90, 91], true) => 'ssl',
            default => 'other',
        };
    }

    public static function resolveUrl(string $base, string $location): ?string
    {
        $location = trim($location);
        if ($location === '' || preg_match('/[\x00-\x1f]/', $location)) {
            return null;
        }
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }
        $b = parse_url($base);
        if (!$b || empty($b['scheme']) || empty($b['host'])) {
            return null;
        }
        $origin = $b['scheme'] . '://' . $b['host'] . (isset($b['port']) ? ':' . $b['port'] : '');
        if (str_starts_with($location, '//')) {
            return $b['scheme'] . ':' . $location;
        }
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $location)) {
            return null;
        }
        $dir = isset($b['path']) ? preg_replace('#/[^/]*$#', '/', $b['path']) : '/';
        return $origin . $dir . $location;
    }
}
