<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

use TodoAcesso\Core\Env;
use TodoAcesso\Core\Logger;

final class AvailabilityChecker
{
    public static function cacheSeconds(): int
    {
        return max(30, Env::int('CHECK_CACHE_SECONDS', 300));
    }

    public static function isStale(array $service): bool
    {
        $at = (int) ($service['last_checked_at'] ?? 0);
        return $at === 0 || time() - $at >= self::cacheSeconds();
    }

    /**
     * Verifica el dominio (HTTPS primero, HTTP como respaldo; apex y luego www) y guarda el resultado.
     * Si corresponde, aprovecha la misma respuesta para importar metadatos.
     *
     * @return array{service:array<string,mixed>,checked:bool}
     */
    public static function check(array $service, bool $force = false, bool $deferImages = true, bool $forceMetadata = false, bool $overwriteManual = false): array
    {
        if (!$force && !self::isStale($service)) {
            return ['service' => $service, 'checked' => false];
        }
        $domain = (string) $service['domain'];
        $needMeta = $forceMetadata || MetadataImporter::isDue($service);
        $maxBytes = $needMeta ? 400000 : 2048;
        $deadline = microtime(true) + 14;

        $hosts = [$domain];
        if (substr_count($domain, '.') === 1 || !str_starts_with($domain, 'www.')) {
            $hosts[] = 'www.' . $domain;
        }

        $result = null;
        $lastError = 'other';
        $skipHost = [];
        foreach ($hosts as $host) {
            foreach (['https', 'http'] as $scheme) {
                $remaining = $deadline - microtime(true);
                if ($remaining < 1.5) {
                    break 2;
                }
                if (isset($skipHost[$host])) {
                    continue;
                }
                $r = HttpClient::get($scheme . '://' . $host . '/', [
                    'timeout' => (int) min(Env::int('CHECK_TIMEOUT_SECONDS', 6), ceil($remaining)),
                    'max_bytes' => $maxBytes,
                ]);
                if ($r['ok']) {
                    $result = $r;
                    break 2;
                }
                $lastError = $r['error'] ?? 'other';
                if (in_array($lastError, ['dns', 'blocked'], true)) {
                    $skipHost[$host] = true;
                }
            }
            if ($lastError === 'blocked') {
                break;
            }
        }

        $status = Status::DOWN;
        $http = null;
        $ms = 0;
        $error = $lastError;
        if ($result !== null) {
            $http = (int) $result['status'];
            $ms = (int) $result['ttfb_ms'];
            $error = $result['error'];
            $slowMs = Env::int('CHECK_SLOW_MS', 3000);
            if ($http >= 500) {
                $status = Status::SERVER_ERROR;
            } elseif ($http >= 400 || $error === 'redirects') {
                $status = Status::RESPONDS;
            } else {
                $status = $ms > $slowMs ? Status::SLOW : Status::OK;
                $error = null;
            }
        }
        ServiceRepository::saveCheck((int) $service['id'], $status, $http, $ms, $error);

        if ($needMeta && $result !== null && $http !== null && $http >= 200 && $http < 400 && str_contains($result['content_type'], 'html')) {
            self::importMetadata($service, $result, $deferImages, $forceMetadata, $overwriteManual);
        } elseif ($needMeta && $result === null && $service['metadata_checked_at'] === null) {
            // No reintentar en cada visita un sitio que no responde: se vuelve a intentar al pasar el período.
            ServiceRepository::update((int) $service['id'], ['metadata_checked_at' => time() - (MetadataImporter::refreshDays() - 1) * 86400]);
        }

        Logger::info('check', ['domain' => $domain, 'status' => $status, 'http' => $http, 'ms' => $ms, 'err' => $error]);
        return ['service' => ServiceRepository::find((int) $service['id']) ?? $service, 'checked' => true];
    }

    private static function importMetadata(array $service, array $result, bool $deferImages, bool $force, bool $overwriteManual): void
    {
        $meta = MetadataImporter::parse($result['body'], $result['final_url']);
        $fields = ['metadata_checked_at' => time()];
        $source = (string) ($service['description_source'] ?? 'none');
        if ($meta['description'] !== null && (in_array($source, ['none', 'auto'], true) || ($force && $overwriteManual))) {
            $fields['description'] = $meta['description'];
            $fields['description_source'] = 'auto';
        }
        // Solo servicios recién creados (nombre deducido del dominio) adoptan el nombre declarado por el sitio.
        if ($source === 'none' && empty($service['description']) && $service['name'] === ServiceRepository::guessName((string) $service['domain'])) {
            $name = MetadataImporter::acceptableName($meta['site_name'] ?? null);
            if ($name !== null) {
                $fields['name'] = $name;
            }
        }
        ServiceRepository::update((int) $service['id'], $fields);

        $fresh = ServiceRepository::find((int) $service['id']) ?? $service;
        $task = static function () use ($fresh, $meta, $force, $overwriteManual): void {
            ImageImporter::importForService($fresh, $meta['icons'], $meta['hero'], $force, $overwriteManual);
        };
        if ($deferImages) {
            Deferred::after($task);
        } else {
            $task();
        }
    }
}
