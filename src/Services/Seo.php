<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

use TodoAcceso\Core\Env;

/** Metadatos SEO y datos estructurados JSON-LD. */
final class Seo
{
    public const SITE = 'Todo Acceso';

    public static function gtmId(): ?string
    {
        $id = Env::str('GTM_ID');
        return preg_match('/^GTM-[A-Z0-9]{4,10}$/', $id) ? $id : null;
    }

    public static function defaultImage(): string
    {
        return app_url('/assets/img/og-default.png');
    }

    /** Título con sufijo de marca solo si entra en ~60 caracteres. */
    public static function title(string $title): string
    {
        $suffix = ' | ' . self::SITE;
        return mb_strlen($title . $suffix) <= 62 ? $title . $suffix : $title;
    }

    /** @return array<string,mixed> */
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => app_url('/#organization'),
            'name' => self::SITE,
            'url' => app_url('/'),
            'logo' => app_url('/assets/img/logo-512.png'),
            'description' => 'Herramienta independiente para comprobar si sitios y aplicaciones funcionan en Argentina y ver reportes de usuarios.',
            'areaServed' => 'AR',
        ];
    }

    /** @return array<string,mixed> */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => app_url('/#website'),
            'url' => app_url('/'),
            'name' => self::SITE,
            'inLanguage' => 'es-AR',
            'publisher' => ['@id' => app_url('/#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => app_url('/buscar') . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param array<int,array{0:string,1:?string}> $crumbs [nombre, ruta o null para el último]
     * @return array<string,mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => [$name, $path]) {
            $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name];
            if ($path !== null) {
                $item['item'] = app_url($path);
            }
            $items[] = $item;
        }
        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /** @param array<int,array{0:string,1:string}> $faq */
    public static function faq(array $faq): ?array
    {
        if (!$faq) {
            return null;
        }
        return [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn ($qa) => [
                '@type' => 'Question',
                'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $faq),
        ];
    }

    /** @param array<int,array<string,mixed>> $nodes */
    public static function graph(array $nodes): string
    {
        return json_ld(['@context' => 'https://schema.org', '@graph' => array_values(array_filter($nodes))]);
    }

    public static function pageNode(string $path, string $name, string $description, ?string $image = null): array
    {
        $node = [
            '@type' => 'WebPage',
            '@id' => app_url($path) . '#webpage',
            'url' => app_url($path),
            'name' => $name,
            'description' => $description,
            'inLanguage' => 'es-AR',
            'isPartOf' => ['@id' => app_url('/#website')],
        ];
        if ($image) {
            $node['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $image];
        }
        return $node;
    }
}
