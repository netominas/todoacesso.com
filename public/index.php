<?php
declare(strict_types=1);

// Servidor embebido de PHP (desarrollo): sirve los archivos estáticos existentes directamente.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

use TodoAcceso\Controllers\AdminController;
use TodoAcceso\Controllers\PageController;
use TodoAcceso\Controllers\PublicController;
use TodoAcceso\Core\Env;
use TodoAcceso\Core\Logger;
use TodoAcceso\Core\Request;
use TodoAcceso\Core\Response;
use TodoAcceso\Core\Router;
use TodoAcceso\Core\View;

$req = Request::fromGlobals();

set_exception_handler(static function (Throwable $e) use ($req): void {
    Logger::error('unhandled', ['type' => $e::class, 'message' => $e->getMessage(), 'where' => basename($e->getFile()) . ':' . $e->getLine()]);
    if (headers_sent()) {
        return;
    }
    if (Env::bool('APP_DEBUG', false)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo $e;
        return;
    }
    try {
        View::error($req, 500, 'Algo salió mal', 'Tuvimos un problema técnico. Probá de nuevo en unos minutos.');
    } catch (Throwable) {
        http_response_code(500);
        echo 'Error interno del servidor.';
    }
});

// Rutas con barra final o mayúsculas en secciones fijas: redirección permanente a la versión canónica.
$path = $req->path;
if ($path !== '/' && str_ends_with($path, '/')) {
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    Response::redirect(rtrim($path, '/') . ($qs !== '' ? '?' . $qs : ''), 301);
}

$r = new Router();
$r->add('GET', '/', [PublicController::class, 'home']);
$r->add('GET', '/buscar', [PublicController::class, 'search']);
$r->add('GET', '/estado/{slug}', [PublicController::class, 'service']);
$r->add('GET', '/estado/{slug}/datos', [PublicController::class, 'data']);
$r->add('POST', '/estado/{slug}/verificar', [PublicController::class, 'recheck']);
$r->add('POST', '/estado/{slug}/reportar', [PublicController::class, 'report']);
$r->add('GET', '/servicios-populares', [PublicController::class, 'popular']);
$r->add('GET', '/sobre-nosotros', [PageController::class, 'about']);
$r->add('GET', '/privacidad', [PageController::class, 'privacy']);
$r->add('GET', '/terminos', [PageController::class, 'terms']);
$r->add('GET', '/sitemap.xml', [PageController::class, 'sitemap']);
$r->add('GET', '/robots.txt', [PageController::class, 'robots']);

$r->add('GET', '/admin', [AdminController::class, 'dashboard']);
$r->add('GET', '/admin/login', [AdminController::class, 'loginForm']);
$r->add('POST', '/admin/login', [AdminController::class, 'login']);
$r->add('POST', '/admin/logout', [AdminController::class, 'logout']);
$r->add('GET', '/admin/servicios', [AdminController::class, 'services']);
$r->add('GET', '/admin/servicios/nuevo', [AdminController::class, 'newForm']);
$r->add('POST', '/admin/servicios/nuevo', [AdminController::class, 'create']);
$r->add('GET', '/admin/servicios/{id}', [AdminController::class, 'edit']);
$r->add('POST', '/admin/servicios/{id}', [AdminController::class, 'update']);
$r->add('POST', '/admin/servicios/{id}/accion', [AdminController::class, 'action']);
$r->add('GET', '/admin/destacados', [AdminController::class, 'featured']);
$r->add('POST', '/admin/destacados', [AdminController::class, 'saveFeatured']);

$r->dispatch($req);
