<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

use DOMDocument;
use DOMXPath;
use TodoAcesso\Core\Env;

/** Extrae descripción, nombre e imágenes declaradas en el HTML de la página principal. */
final class MetadataImporter
{
    public static function refreshDays(): int
    {
        return max(1, Env::int('METADATA_REFRESH_DAYS', 30));
    }

    public static function isDue(array $service): bool
    {
        $at = (int) ($service['metadata_checked_at'] ?? 0);
        return $at === 0 || time() - $at > self::refreshDays() * 86400;
    }

    /**
     * @return array{description:?string,site_name:?string,title:?string,icons:array<int,string>,hero:?string}
     */
    public static function parse(string $html, string $baseUrl): array
    {
        $out = ['description' => null, 'site_name' => null, 'title' => null, 'icons' => [], 'hero' => null];
        $end = stripos($html, '</head>');
        if ($end !== false) {
            $html = substr($html, 0, $end + 7);
        }
        if (trim($html) === '') {
            return $out;
        }
        $prev = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $xp = new DOMXPath($doc);

        $meta = [];
        foreach ($xp->query('//meta[@content]') ?: [] as $m) {
            $key = strtolower(trim($m->getAttribute('property') ?: $m->getAttribute('name')));
            if ($key !== '' && !isset($meta[$key])) {
                $meta[$key] = trim($m->getAttribute('content'));
            }
        }

        $desc = $meta['description'] ?? ($meta['og:description'] ?? null);
        $ogDesc = $meta['og:description'] ?? null;
        $out['description'] = self::cleanText($desc ?? $ogDesc ?? '', 300) ?: null;
        $out['site_name'] = self::cleanText($meta['og:site_name'] ?? '', 60) ?: null;
        $titleNode = $xp->query('//title')->item(0);
        $out['title'] = $titleNode ? (self::cleanText($titleNode->textContent, 120) ?: null) : null;

        $icons = [];
        foreach ($xp->query('//link[@href][@rel]') ?: [] as $l) {
            $rel = strtolower($l->getAttribute('rel'));
            if (!preg_match('/(^|\s)(apple-touch-icon(-precomposed)?|icon|shortcut)(\s|$)/', $rel)) {
                continue;
            }
            $href = trim($l->getAttribute('href'));
            $type = strtolower($l->getAttribute('type'));
            if ($href === '' || $type === 'image/svg+xml' || preg_match('/\.svg(\?|$)/i', $href)) {
                continue;
            }
            $url = self::absolute($baseUrl, $href);
            if ($url === null) {
                continue;
            }
            $size = 0;
            if (preg_match('/(\d+)x\d+/', $l->getAttribute('sizes'), $m)) {
                $size = (int) $m[1];
            }
            $score = (str_contains($rel, 'apple-touch-icon') ? 1000 : 0) + min($size, 512);
            $icons[$url] = max($icons[$url] ?? 0, $score);
        }
        arsort($icons);
        $out['icons'] = array_keys($icons);
        $origin = (parse_url($baseUrl, PHP_URL_SCHEME) ?? 'https') . '://' . (parse_url($baseUrl, PHP_URL_HOST) ?? '');
        $out['icons'][] = $origin . '/favicon.ico';
        $out['icons'] = array_slice(array_values(array_unique($out['icons'])), 0, 5);

        $heroRaw = $meta['og:image'] ?? ($meta['og:image:url'] ?? ($meta['twitter:image'] ?? ($meta['twitter:image:src'] ?? '')));
        $out['hero'] = $heroRaw !== '' ? self::absolute($baseUrl, $heroRaw) : null;
        return $out;
    }

    public static function absolute(string $base, string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '' || str_starts_with($ref, 'data:')) {
            return null;
        }
        $url = HttpClient::resolveUrl($base, $ref);
        if ($url === null || !preg_match('#^https?://#i', $url) || strlen($url) > 600) {
            return null;
        }
        return $url;
    }

    public static function cleanText(string $text, int $max): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        return $text === '' ? '' : str_limit($text, $max);
    }

    /** Un nombre propuesto solo se usa si parece una marca razonable. */
    public static function acceptableName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 40 || preg_match('#[<>{}|/\\\\]|https?:#i', $name)) {
            return null;
        }
        return $name;
    }
}
