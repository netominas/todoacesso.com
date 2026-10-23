<?php
declare(strict_types=1);

namespace TodoAcesso\Controllers;

use TodoAcesso\Core\Env;
use TodoAcesso\Core\Request;
use TodoAcesso\Core\Response;
use TodoAcesso\Core\View;
use TodoAcesso\Services\Seo;
use TodoAcesso\Services\ServiceRepository;

/** Páginas legales/institucionales, sitemap.xml y robots.txt. */
final class PageController
{
    private const PAGES = [
        'sobre-nosotros' => ['Sobre Todo Acesso', 'about', 'Conocé Todo Acesso, la herramienta independiente para comprobar si sitios y aplicaciones funcionan en Argentina.'],
        'privacidad' => ['Política de privacidad', 'privacy', 'Cómo trata Todo Acesso los datos: reportes anónimos, ubicación voluntaria y aproximada, cookies y servicios de terceros.'],
        'terminos' => ['Términos y condiciones', 'terms', 'Condiciones de uso de Todo Acesso: información orientativa, verificaciones puntuales, independencia de las marcas y uso responsable.'],
    ];

    public static function show(Request $req, string $slug): void
    {
        $page = self::PAGES[$slug] ?? null;
        if ($page === null) {
            Response::notFound($req);
        }
        [$title, $view, $description] = $page;
        $path = '/' . $slug;
        $seo = [
            'title' => $title . ' | Todo Acesso',
            'description' => $description,
            'canonical' => app_url($path),
            'robots' => 'index,follow,max-image-preview:large',
            'image' => Seo::defaultImage(),
            'jsonld' => Seo::graph([
                Seo::organization(),
                Seo::website(),
                Seo::pageNode($path, $title, $description),
                Seo::breadcrumbs([['Inicio', '/'], [$title, $path]]),
            ]),
        ];
        View::page($req, 'pages/' . $view, [
            'title' => $title,
            'crumbs' => [['Inicio', '/'], [$title, null]],
            'contactEmail' => Env::str('CONTACT_EMAIL', 'contacto@todoacesso.com'),
            'updated' => (int) filemtime(BASE_PATH . '/views/pages/' . $view . '.php'),
        ], $seo);
    }

    public static function about(Request $req): void
    {
        self::show($req, 'sobre-nosotros');
    }

    public static function privacy(Request $req): void
    {
        self::show($req, 'privacidad');
    }

    public static function terms(Request $req): void
    {
        self::show($req, 'terminos');
    }

    public static function robots(Request $req): void
    {
        $lines = ['User-agent: *'];
        if (Env::isLocal()) {
            $lines[] = 'Disallow: /';
        } else {
            $lines = array_merge($lines, [
                'Disallow: /admin',
                'Disallow: /buscar',
                'Disallow: /estado/*/verificar',
                'Disallow: /estado/*/reportar',
                'Disallow: /estado/*/datos',
                'Disallow: /*?r=',
                'Allow: /assets/',
                'Allow: /uploads/',
                '',
                'Sitemap: ' . app_url('/sitemap.xml'),
            ]);
        }
        header('Cache-Control: public, max-age=3600');
        Response::text(implode("\n", $lines) . "\n", 'text/plain; charset=utf-8');
    }

    public static function sitemap(Request $req): void
    {
        $iso = static fn (int $ts): string => gmdate('Y-m-d\TH:i:s\Z', $ts);
        $urls = [];
        $rows = ServiceRepository::sitemapRows(40000);
        $latest = 0;
        foreach ($rows as $r) {
            $latest = max($latest, (int) $r['updated_at'], (int) ($r['metadata_checked_at'] ?? 0));
        }
        $latest = $latest ?: time();

        $urls[] = ['/', $iso($latest), 'daily', '1.0'];
        $urls[] = ['/servicios-populares', $iso($latest), 'daily', '0.8'];
        foreach (self::PAGES as $slug => [, $view]) {
            $urls[] = ['/' . $slug, $iso((int) filemtime(BASE_PATH . '/views/pages/' . $view . '.php')), 'yearly', '0.3'];
        }
        foreach ($rows as $r) {
            $last = max((int) $r['updated_at'], (int) ($r['metadata_checked_at'] ?? 0));
            $urls[] = ['/estado/' . $r['slug'], $iso($last), 'hourly', '0.9'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (array_slice($urls, 0, 45000) as [$path, $lastmod, $freq, $prio]) {
            $xml .= '  <url><loc>' . htmlspecialchars(app_url($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . $lastmod . '</lastmod><changefreq>' . $freq . '</changefreq><priority>' . $prio . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";
        header('Cache-Control: public, max-age=900');
        Response::text($xml, 'application/xml; charset=utf-8');
    }
}
