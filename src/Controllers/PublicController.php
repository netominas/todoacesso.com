<?php
declare(strict_types=1);

namespace TodoAcesso\Controllers;

use TodoAcesso\Content\Categories;
use TodoAcesso\Content\ServiceContent;
use TodoAcesso\Core\Database as DB;
use TodoAcesso\Core\Env;
use TodoAcesso\Core\Logger;
use TodoAcesso\Core\RateLimiter;
use TodoAcesso\Core\Request;
use TodoAcesso\Core\Response;
use TodoAcesso\Core\Security;
use TodoAcesso\Core\View;
use TodoAcesso\Services\ActivityData;
use TodoAcesso\Services\AvailabilityChecker;
use TodoAcesso\Services\DomainNormalizer;
use TodoAcesso\Services\ReportService;
use TodoAcesso\Services\Seo;
use TodoAcesso\Services\ServiceRepository;

final class PublicController
{
    private const FLASH = [
        'created' => ['ok', '¡Gracias! Registramos tu reporte.'],
        'duplicate' => ['info', 'Ya registramos un reporte tuyo con este problema hace poco. Gracias por avisar.'],
        'cooldown' => ['info', 'Esperá un par de minutos antes de enviar otro reporte para este servicio.'],
        'rate' => ['error', 'Enviaste muchos reportes seguidos. Probá de nuevo más tarde.'],
        'invalid' => ['error', 'Elegí un tipo de problema para poder enviar el reporte.'],
        'token' => ['error', 'El formulario venció. Recargá la página e intentá de nuevo.'],
    ];

    public static function home(Request $req): void
    {
        $featured = ServiceRepository::featured(24);
        $error = $req->q('e') === 'rate' ? 'Hiciste muchas consultas seguidas. Esperá unos minutos e intentá de nuevo.' : null;
        $seo = [
            'title' => 'Todo Acesso: ¿está caído? Comprobá el estado de sitios y apps en Argentina',
            'description' => 'Comprobá en segundos si un sitio o una app funciona en Argentina. Estado, tiempo de respuesta y reportes de usuarios de WhatsApp, Instagram, ChatGPT, bancos y más.',
            'canonical' => app_url('/'),
            'robots' => 'index,follow,max-image-preview:large',
            'image' => Seo::defaultImage(),
            'jsonld' => Seo::graph([
                Seo::organization(),
                Seo::website(),
                Seo::pageNode('/', 'Todo Acesso: comprobá el estado de sitios y apps en Argentina', 'Comprobá en segundos si un sitio o una app funciona en Argentina.'),
            ]),
        ];
        View::page($req, 'home', ['featured' => $featured, 'error' => $error, 'query' => '', 'bodyClass' => 'is-home'], $seo);
    }

    public static function search(Request $req): void
    {
        $raw = $req->q('q');
        if (!RateLimiter::hit('search:' . $req->clientKey(), Env::int('RL_SEARCH_PER_10MIN', 40), 600)) {
            self::searchError($req, $raw, 'Hiciste muchas consultas seguidas. Esperá unos minutos e intentá de nuevo.', 429);
        }
        $norm = DomainNormalizer::normalize($raw);
        if (!$norm['ok']) {
            self::searchError($req, $raw, (string) $norm['message'], 422);
        }
        $domain = (string) $norm['domain'];

        $service = ServiceRepository::findByDomain($domain);
        $isNew = false;
        if ($service === null) {
            if (!RateLimiter::hit('newsvc:' . $req->clientKey(), Env::int('RL_NEW_SERVICE_PER_HOUR', 8), 3600)) {
                self::searchError($req, $raw, 'Alcanzaste el límite de sitios nuevos por hora. Probá más tarde.', 429);
            }
            $service = ServiceRepository::create($domain, ['indexable' => 0]);
            $isNew = true;
        }

        $res = AvailabilityChecker::check($service);
        $service = $res['service'];

        // Un dominio nuevo que no existe en el DNS no se registra: evita páginas basura.
        if ($isNew && $service['last_status'] === 'down' && in_array($service['last_error'], ['dns', 'blocked'], true)) {
            ServiceRepository::delete((int) $service['id']);
            self::searchError($req, $raw, 'No encontramos el dominio "' . $domain . '". Revisá que esté bien escrito.', 422);
        }

        ServiceRepository::incrementSearches((int) $service['id']);
        Response::redirect(service_path($service), 303);
    }

    private static function searchError(Request $req, string $raw, string $message, int $code): never
    {
        $seo = [
            'title' => 'Todo Acesso: comprobá el estado de sitios y apps en Argentina',
            'description' => 'Comprobá en segundos si un sitio o una app funciona en Argentina.',
            'canonical' => app_url('/'),
            'robots' => 'noindex,follow',
            'image' => Seo::defaultImage(),
        ];
        View::page($req, 'home', [
            'featured' => ServiceRepository::featured(24),
            'error' => $message,
            'query' => mb_substr($raw, 0, 200),
            'bodyClass' => 'is-home',
        ], $seo, 'layout', $code, false);
    }

    private static function loadService(Request $req, string $slug): array
    {
        if (!preg_match('/^[A-Za-z0-9-]{1,120}$/', $slug)) {
            Response::notFound($req);
        }
        if ($slug !== strtolower($slug)) {
            Response::redirect('/estado/' . strtolower($slug), 301);
        }
        return ServiceRepository::findBySlug($slug) ?? Response::notFound($req);
    }

    /** Redirige a la dirección canónica si llegaron con barra final u otras variantes. */
    public static function service(Request $req, string $slug): void
    {
        $service = self::loadService($req, $slug);

        // Servicio sin ninguna verificación (p. ej. creado desde el panel): se verifica una vez al abrirlo.
        if ($service['last_checked_at'] === null && !$req->isBot()) {
            $service = AvailabilityChecker::check($service)['service'];
        }

        $content = ServiceContent::build($service);
        $activity = ActivityData::forService((int) $service['id']);
        $path = service_path($service);
        $iconUrl = upload_url($service['icon_path']);
        $heroUrl = upload_url($service['hero_image_path']);
        $heroDims = $heroUrl ? @getimagesize(BASE_PATH . '/public/uploads/' . $service['hero_image_path']) : false;
        $flashKey = $req->q('r');
        $flash = self::FLASH[$flashKey] ?? null;

        $title = Seo::title($content['title']);
        $desc = $content['meta'];
        $ogImage = $heroUrl ? app_url($heroUrl) : Seo::defaultImage();
        $related = ServiceRepository::related($service, 6);
        $crumbs = [['Inicio', '/'], ['Servicios', '/servicios-populares'], [$service['name'], null]];

        $nodes = [
            Seo::organization(),
            Seo::website(),
            Seo::pageNode($path, $content['h1'], $desc, $ogImage),
            Seo::breadcrumbs([['Inicio', '/'], ['Servicios populares', '/servicios-populares'], [$service['name'], $path]]),
            Seo::faq($content['faq']),
        ];
        $seo = [
            'title' => $title,
            'description' => $desc,
            'canonical' => app_url($path),
            'robots' => $service['indexable'] ? 'index,follow,max-image-preview:large' : 'noindex,follow',
            'image' => $ogImage,
            'image_alt' => 'Estado de ' . $service['name'] . ' en Todo Acesso',
            'type' => 'article',
            'jsonld' => Seo::graph($nodes),
        ];

        View::page($req, 'service', [
            'service' => $service,
            'content' => $content,
            'activity' => $activity,
            'iconUrl' => $iconUrl,
            'heroUrl' => $heroUrl,
            'heroDims' => $heroDims ?: null,
            'related' => $related,
            'crumbs' => $crumbs,
            'problemTypes' => ReportService::problemTypes(),
            'token' => Security::publicToken($req),
            'flash' => $flash,
            'flashKey' => $flashKey,
            'stale' => AvailabilityChecker::isStale($service),
            'categoryLabel' => Categories::LABELS[$service['category']] ?? 'Sitio web',
            'needsMap' => true,
            'bodyClass' => 'is-service',
        ], $seo, 'layout', 200, true);
    }

    /** Fragmentos HTML actualizables por JavaScript. */
    private static function fragments(array $service): array
    {
        $activity = ActivityData::forService((int) $service['id']);
        return [
            'status' => View::partial('partials/status_card', ['service' => $service, 'activity' => $activity, 'stale' => AvailabilityChecker::isStale($service)]),
            'chart' => View::partial('partials/chart', ['activity' => $activity, 'service' => $service]),
            'breakdown' => View::partial('partials/breakdown', ['activity' => $activity]),
            'regions' => $activity['regions'],
            'total' => $activity['total'],
            'level' => $activity['level'],
        ];
    }

    public static function data(Request $req, string $slug): void
    {
        $service = self::loadService($req, $slug);
        Response::json(self::fragments($service));
    }

    public static function recheck(Request $req, string $slug): void
    {
        $service = self::loadService($req, $slug);
        $manual = $req->p('mode') !== 'auto';
        $back = service_path($service);

        if (!Security::checkPublicToken($req, $req->p('_t'), 0)) {
            $req->wantsJson() ? Response::json(['ok' => false, 'message' => self::FLASH['token'][1]], 400) : Response::redirect($back . '?r=token', 303);
        }
        if (!RateLimiter::hit('recheck:' . $req->clientKey(), Env::int('RL_RECHECK_PER_10MIN', 30), 600)) {
            $req->wantsJson() ? Response::json(['ok' => false, 'message' => self::FLASH['rate'][1]], 429) : Response::redirect($back . '?r=rate', 303);
        }
        // Verificación manual: como máximo una por minuto por servicio. Automática: solo si venció la caché.
        $last = (int) ($service['last_checked_at'] ?? 0);
        $force = $manual && time() - $last >= 60;
        $res = AvailabilityChecker::check($service, $force);

        if ($req->wantsJson()) {
            Response::json(['ok' => true, 'checked' => $res['checked']] + self::fragments($res['service']));
        }
        Response::redirect($back, 303);
    }

    public static function report(Request $req, string $slug): void
    {
        $service = self::loadService($req, $slug);
        $back = service_path($service);
        $respond = static function (string $key, array $extra = []) use ($req, $back, $service): never {
            [$tone, $message] = self::FLASH[$key];
            if ($req->wantsJson()) {
                Response::json(['ok' => $key === 'created', 'status' => $key, 'tone' => $tone, 'message' => $message] + $extra, $key === 'rate' ? 429 : 200);
            }
            Response::redirect($back . '?r=' . $key . '#reportar', 303);
        };

        // Honeypot: los bots completan campos ocultos; se simula éxito sin guardar nada.
        if ($req->p('website') !== '') {
            $respond('created');
        }
        if (!Security::checkPublicToken($req, $req->p('_t'), 2)) {
            $respond('token');
        }
        $lat = ReportService::parseCoordinate($req->p('lat'), -90, 90);
        $lon = ReportService::parseCoordinate($req->p('lon'), -180, 180);

        try {
            $result = ReportService::submit($req, $service, $req->p('problem'), $lat, $lon);
        } catch (\Throwable $e) {
            Logger::error('report_failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        $extra = $result === 'created' ? self::fragments($service) : [];
        $respond($result, $extra);
    }

    public static function popular(Request $req): void
    {
        $perPage = 30;
        $page = max(1, (int) $req->q('pagina'));
        $data = ServiceRepository::popular($page, $perPage);
        $pages = max(1, (int) ceil($data['total'] / $perPage));
        if ($page > $pages) {
            Response::redirect('/servicios-populares' . ($pages > 1 ? '?pagina=' . $pages : ''), 302);
        }
        $canonical = app_url('/servicios-populares') . ($page > 1 ? '?pagina=' . $page : '');
        $seo = [
            'title' => 'Servicios más consultados en Argentina' . ($page > 1 ? ' (página ' . $page . ')' : '') . ' | Todo Acesso',
            'description' => 'Ranking de los sitios y aplicaciones más consultados en Todo Acesso: estado actual, tiempo de respuesta y acceso rápido a cada servicio.',
            'canonical' => $canonical,
            'robots' => 'index,follow,max-image-preview:large',
            'image' => Seo::defaultImage(),
            'jsonld' => Seo::graph([
                Seo::organization(),
                Seo::website(),
                Seo::pageNode('/servicios-populares', 'Servicios más consultados en Argentina', 'Ranking de los servicios más consultados en Todo Acesso.'),
                Seo::breadcrumbs([['Inicio', '/'], ['Servicios populares', '/servicios-populares']]),
            ]),
            'prev' => $page > 1 ? app_url('/servicios-populares') . ($page > 2 ? '?pagina=' . ($page - 1) : '') : null,
            'next' => $page < $pages ? app_url('/servicios-populares?pagina=' . ($page + 1)) : null,
        ];
        View::page($req, 'popular', [
            'rows' => $data['rows'], 'total' => $data['total'], 'page' => $page, 'pages' => $pages, 'perPage' => $perPage,
            'crumbs' => [['Inicio', '/'], ['Servicios populares', null]],
        ], $seo);
    }

    public static function stats(): array
    {
        return [
            'services' => (int) DB::value('SELECT COUNT(*) FROM services'),
        ];
    }
}
