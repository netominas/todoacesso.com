<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

/** Protección SSRF: solo se conectan hosts cuyas IPs resueltas son todas públicas. */
final class SsrfGuard
{
    private const V4_BLOCKED = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
        '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15',
        '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
    ];

    private const V6_BLOCKED = [
        '::/128', '::1/128', '100::/64', '2001:db8::/32', '2001::/32', 'fc00::/7', 'fe80::/10', 'ff00::/8',
    ];

    public static function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $bin = inet_pton($ip);
        if ($bin === false) {
            return false;
        }
        if (strlen($bin) === 4) {
            return !self::inAny($bin, self::V4_BLOCKED);
        }
        // IPv6 que embebe IPv4: ::ffff:a.b.c.d, NAT64 64:ff9b::/96, 6to4 2002::/16
        if (str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) {
            return self::isPublicIp(inet_ntop(substr($bin, 12, 4)) ?: '0.0.0.0');
        }
        if (str_starts_with($bin, hex2bin('0064ff9b') . str_repeat("\0", 8))) {
            return self::isPublicIp(inet_ntop(substr($bin, 12, 4)) ?: '0.0.0.0');
        }
        if (str_starts_with($bin, "\x20\x02")) {
            return self::isPublicIp(inet_ntop(substr($bin, 2, 4)) ?: '0.0.0.0');
        }
        return !self::inAny($bin, self::V6_BLOCKED);
    }

    /**
     * Resuelve el host y devuelve sus IPs. Si alguna IP no es pública se bloquea todo el host.
     * @return array{ok:bool,ips:array<int,string>,error?:string}
     */
    public static function resolve(string $host): array
    {
        $ips = [];
        $a = @dns_get_record($host, DNS_A);
        if (is_array($a)) {
            foreach ($a as $r) {
                if (!empty($r['ip'])) {
                    $ips[] = $r['ip'];
                }
            }
        }
        $aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaa)) {
            foreach ($aaaa as $r) {
                if (!empty($r['ipv6'])) {
                    $ips[] = $r['ipv6'];
                }
            }
        }
        if (!$ips) {
            $legacy = @gethostbynamel($host);
            if (is_array($legacy)) {
                $ips = $legacy;
            }
        }
        $ips = array_values(array_unique($ips));
        if (!$ips) {
            return ['ok' => false, 'ips' => [], 'error' => 'dns'];
        }
        foreach ($ips as $ip) {
            if (!self::isPublicIp($ip)) {
                return ['ok' => false, 'ips' => [], 'error' => 'blocked'];
            }
        }
        return ['ok' => true, 'ips' => $ips];
    }

    /** @param array<int,string> $cidrs */
    private static function inAny(string $bin, array $cidrs): bool
    {
        foreach ($cidrs as $cidr) {
            [$net, $bits] = explode('/', $cidr);
            $netBin = inet_pton($net);
            if ($netBin === false || strlen($netBin) !== strlen($bin)) {
                continue;
            }
            if (self::matches($bin, $netBin, (int) $bits)) {
                return true;
            }
        }
        return false;
    }

    private static function matches(string $ip, string $net, int $bits): bool
    {
        $bytes = intdiv($bits, 8);
        if ($bytes > 0 && substr($ip, 0, $bytes) !== substr($net, 0, $bytes)) {
            return false;
        }
        $rem = $bits % 8;
        if ($rem === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rem)) & 0xFF;
        return (ord($ip[$bytes]) & $mask) === (ord($net[$bytes]) & $mask);
    }
}
