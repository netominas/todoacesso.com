<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

final class Request
{
    public readonly string $method;
    public readonly string $path;

    /** @param array<string,mixed> $server */
    public function __construct(
        public readonly array $query,
        public readonly array $post,
        public readonly array $server,
        public readonly array $files = []
    ) {
        $this->method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = rawurldecode($path);
        $this->path = $path === '' ? '/' : $path;
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    public function q(string $key): string
    {
        $v = $this->query[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    public function p(string $key): string
    {
        $v = $this->post[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($this->server[$key] ?? '');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept'), 'application/json')
            || strtolower($this->header('X-Requested-With')) === 'fetch';
    }

    public function isHttps(): bool
    {
        if (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') {
            return true;
        }
        return $this->fromTrustedProxy() && strtolower($this->header('X-Forwarded-Proto')) === 'https';
    }

    private function fromTrustedProxy(): bool
    {
        $trusted = array_filter(array_map('trim', explode(',', Env::str('TRUSTED_PROXIES'))));
        return $trusted && in_array((string) ($this->server['REMOTE_ADDR'] ?? ''), $trusted, true);
    }

    public function ip(): string
    {
        $remote = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        if ($this->fromTrustedProxy()) {
            $cf = $this->header('CF-Connecting-IP');
            if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
            $xff = trim(explode(',', $this->header('X-Forwarded-For'))[0]);
            if ($xff !== '' && filter_var($xff, FILTER_VALIDATE_IP)) {
                return $xff;
            }
        }
        return $remote;
    }

    /** IPv4 completa; IPv6 reducida a su /64 (los usuarios rotan las direcciones de la subred). */
    private function ipNet(): string
    {
        $ip = $this->ip();
        if (str_contains($ip, ':')) {
            $bin = @inet_pton($ip);
            if ($bin !== false) {
                return bin2hex(substr($bin, 0, 8));
            }
        }
        return $ip;
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 300);
    }

    /** Identificador anónimo para rate limiting (no reversible sin APP_KEY). */
    public function clientKey(): string
    {
        return substr(hash_hmac('sha256', 'rl|' . $this->ipNet(), Security::key()), 0, 32);
    }

    /** Hash anónimo del autor de un reporte: datos técnicos + clave privada. */
    public function reporterHash(): string
    {
        return hash_hmac('sha256', 'rep|' . $this->ipNet() . '|' . $this->userAgent(), Security::key());
    }

    public function isBot(): bool
    {
        $ua = strtolower($this->userAgent());
        if ($ua === '') {
            return true;
        }
        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|httpclient|headless/', $ua);
    }
}
