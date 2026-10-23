<?php
declare(strict_types=1);

namespace TodoAcesso\Controllers;

use TodoAcesso\Content\Categories;
use TodoAcesso\Core\Auth;
use TodoAcesso\Core\Database as DB;
use TodoAcesso\Core\Request;
use TodoAcesso\Core\Response;
use TodoAcesso\Core\View;
use TodoAcesso\Services\AvailabilityChecker;
use TodoAcesso\Services\DomainNormalizer;
use TodoAcesso\Services\ImageImporter;
use TodoAcesso\Services\ServiceRepository;

final class AdminController
{
    private static function page(Request $req, string $view, array $data, string $title): never
    {
        $seo = ['title' => $title . ' | Admin Todo Acesso', 'description' => '', 'robots' => 'noindex,nofollow', 'canonical' => null];
        $data['flash'] = Auth::flash();
        $data['bodyClass'] = 'is-admin';
        View::page($req, 'admin/' . $view, $data, $seo, 'admin/layout', 200, false);
    }

    private static function guard(Request $req): void
    {
        Auth::require($req);
    }

    private static function guardPost(Request $req): void
    {
        Auth::require($req);
        Auth::verifyCsrf($req);
    }

    public static function loginForm(Request $req): void
    {
        Auth::start($req);
        if (Auth::check()) {
            Response::redirect('/admin', 303);
        }
        $seo = ['title' => 'Ingresar | Admin Todo Acesso', 'description' => '', 'robots' => 'noindex,nofollow', 'canonical' => null];
        View::page($req, 'admin/login', ['error' => null, 'bodyClass' => 'is-admin'], $seo, 'admin/layout', 200, false);
    }

    public static function login(Request $req): void
    {
        Auth::start($req);
        $error = Auth::attempt($req, $req->p('user'), $req->p('password'));
        if ($error === null) {
            Response::redirect('/admin', 303);
        }
        $seo = ['title' => 'Ingresar | Admin Todo Acesso', 'description' => '', 'robots' => 'noindex,nofollow', 'canonical' => null];
        View::page($req, 'admin/login', ['error' => $error, 'bodyClass' => 'is-admin'], $seo, 'admin/layout', 401, false);
    }

    public static function logout(Request $req): void
    {
        Auth::start($req);
        Auth::verifyCsrf($req);
        Auth::destroy();
        Response::redirect('/admin/login', 303);
    }

    public static function dashboard(Request $req): void
    {
        self::guard($req);
        $since = time() - 86400;
        $stats = [
            'services' => (int) DB::value('SELECT COUNT(*) FROM services'),
            'indexable' => (int) DB::value('SELECT COUNT(*) FROM services WHERE indexable = 1'),
            'featured' => (int) DB::value('SELECT COUNT(*) FROM services WHERE featured = 1'),
            'searches' => (int) DB::value('SELECT COALESCE(SUM(search_count), 0) FROM services'),
            'reports_total' => (int) DB::value('SELECT COUNT(*) FROM reports'),
            'reports_24h' => (int) DB::value('SELECT COUNT(*) FROM reports WHERE created_at >= ?', [$since]),
        ];
        $top = DB::all('SELECT s.id, s.name, s.domain, s.slug, s.search_count, (SELECT COUNT(*) FROM reports r WHERE r.service_id = s.id AND r.created_at >= ?) AS r24 FROM services s ORDER BY s.search_count DESC LIMIT 8', [$since]);
        $pending = DB::all('SELECT id, name, domain, slug, search_count FROM services WHERE indexable = 0 ORDER BY search_count DESC, id DESC LIMIT 8');
        self::page($req, 'dashboard', ['stats' => $stats, 'top' => $top, 'pending' => $pending], 'Panel');
    }

    public static function services(Request $req): void
    {
        self::guard($req);
        $q = $req->q('q');
        $filter = in_array($req->q('f'), ['featured', 'indexable', 'pending'], true) ? $req->q('f') : '';
        $page = max(1, (int) $req->q('p'));
        $data = ServiceRepository::adminList($q, $filter, $page, 25);
        self::page($req, 'services', [
            'rows' => $data['rows'], 'total' => $data['total'], 'q' => $q, 'filter' => $filter, 'page' => $page, 'pages' => max(1, (int) ceil($data['total'] / 25)),
        ], 'Servicios');
    }

    public static function newForm(Request $req): void
    {
        self::guard($req);
        self::page($req, 'new', ['error' => null, 'value' => ''], 'Nuevo servicio');
    }

    public static function create(Request $req): void
    {
        self::guardPost($req);
        $norm = DomainNormalizer::normalize($req->p('domain'));
        if (!$norm['ok']) {
            self::page($req, 'new', ['error' => $norm['message'], 'value' => $req->p('domain')], 'Nuevo servicio');
        }
        $existing = ServiceRepository::findByDomain((string) $norm['domain']);
        if ($existing) {
            Auth::flash('Ese dominio ya estaba cargado. Te llevamos a su ficha.', 'info');
            Response::redirect('/admin/servicios/' . $existing['id'], 303);
        }
        $service = ServiceRepository::create((string) $norm['domain'], ['indexable' => 0]);
        AvailabilityChecker::check($service, true, false, true);
        Auth::flash('Servicio creado. Revisá el contenido antes de activar la indexación.');
        Response::redirect('/admin/servicios/' . $service['id'], 303);
    }

    private static function loadService(Request $req, string $id): array
    {
        return ServiceRepository::find((int) $id) ?? Response::notFound($req);
    }

    public static function edit(Request $req, string $id): void
    {
        self::guard($req);
        $service = self::loadService($req, $id);
        $reports = (int) DB::value('SELECT COUNT(*) FROM reports WHERE service_id = ?', [$service['id']]);
        self::page($req, 'edit', [
            'service' => $service, 'reports' => $reports,
            'iconUrl' => upload_url($service['icon_path']), 'heroUrl' => upload_url($service['hero_image_path']),
            'categories' => Categories::LABELS,
        ], 'Editar ' . $service['name']);
    }

    public static function update(Request $req, string $id): void
    {
        self::guardPost($req);
        $service = self::loadService($req, $id);

        $name = trim($req->p('name'));
        if ($name === '' || mb_strlen($name) > 120) {
            Auth::flash('El nombre es obligatorio (máximo 120 caracteres).', 'error');
            Response::redirect('/admin/servicios/' . $service['id'], 303);
        }
        $category = array_key_exists($req->p('category'), Categories::LABELS) ? $req->p('category') : 'sitio';
        $description = trim($req->p('description'));
        $editorial = trim($req->p('editorial'));

        $fields = [
            'name' => $name,
            'category' => $category,
            'editorial' => $editorial === '' ? null : mb_substr($editorial, 0, 20000),
            'featured' => $req->p('featured') === '1' ? 1 : 0,
            'featured_order' => max(0, min(100000, (int) $req->p('featured_order'))),
            'indexable' => $req->p('indexable') === '1' ? 1 : 0,
        ];
        if ($description !== ($service['description'] ?? '')) {
            $fields['description'] = $description === '' ? null : mb_substr($description, 0, 600);
            $fields['description_source'] = $description === '' ? 'none' : 'manual';
        }

        $errors = [];
        foreach (['icon' => 'icon_file', 'hero' => 'hero_file'] as $kind => $input) {
            $file = $req->files[$input] ?? null;
            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            [$path, $err] = ImageImporter::saveUpload($service, $file, $kind);
            if ($path === null) {
                $errors[] = $err;
                continue;
            }
            $col = $kind === 'icon' ? 'icon_path' : 'hero_image_path';
            $src = $kind === 'icon' ? 'icon_source_url' : 'hero_image_source_url';
            ImageImporter::removeFile($service[$col] ?? null);
            $fields[$col] = $path;
            $fields[$src] = 'manual';
        }
        foreach (['icon' => ['icon_path', 'icon_source_url'], 'hero' => ['hero_image_path', 'hero_image_source_url']] as $kind => [$col, $src]) {
            if ($req->p('remove_' . $kind) === '1' && !isset($fields[$col])) {
                ImageImporter::removeFile($service[$col] ?? null);
                $fields[$col] = null;
                $fields[$src] = null;
            }
        }
        ServiceRepository::update((int) $service['id'], $fields);

        if ($errors) {
            Auth::flash('Se guardó el servicio, pero: ' . implode(' ', $errors), 'error');
        } else {
            Auth::flash('Cambios guardados.');
        }
        Response::redirect('/admin/servicios/' . $service['id'], 303);
    }

    public static function action(Request $req, string $id): void
    {
        self::guardPost($req);
        $service = self::loadService($req, $id);
        switch ($req->p('do')) {
            case 'recheck':
                $res = AvailabilityChecker::check($service, true, false);
                Auth::flash('Verificación realizada: ' . \TodoAcesso\Services\Status::label($res['service']['last_status']) . '.');
                break;
            case 'import':
                $overwrite = $req->p('overwrite') === '1';
                $res = AvailabilityChecker::check($service, true, false, true, $overwrite);
                Auth::flash($res['service']['last_status'] === 'down'
                    ? 'No se pudo importar: el sitio no respondió.'
                    : 'Metadatos e imágenes importados (se conservó el contenido manual' . ($overwrite ? ' salvo lo que marcaste para reemplazar' : '') . ').');
                break;
            case 'delete':
                ImageImporter::removeFile($service['icon_path'] ?? null);
                ImageImporter::removeFile($service['hero_image_path'] ?? null);
                ServiceRepository::delete((int) $service['id']);
                Auth::flash('Servicio eliminado junto con sus reportes.');
                Response::redirect('/admin/servicios', 303);
            // no break: redirect() termina la ejecución
            case 'clear_reports':
                DB::run('DELETE FROM reports WHERE service_id = ?', [$service['id']]);
                Auth::flash('Reportes eliminados.');
                break;
            default:
                Auth::flash('Acción no válida.', 'error');
        }
        Response::redirect('/admin/servicios/' . $service['id'], 303);
    }

    public static function featured(Request $req): void
    {
        self::guard($req);
        self::page($req, 'featured', ['rows' => ServiceRepository::featured(200)], 'Destacados');
    }

    public static function saveFeatured(Request $req): void
    {
        self::guardPost($req);
        $orders = $req->post['order'] ?? [];
        if (is_array($orders)) {
            foreach ($orders as $id => $order) {
                if (is_numeric($id) && is_scalar($order) && is_numeric($order)) {
                    ServiceRepository::update((int) $id, ['featured_order' => max(0, min(100000, (int) $order))]);
                }
            }
        }
        Auth::flash('Orden de destacados actualizado.');
        Response::redirect('/admin/destacados', 303);
    }
}
