<?php
declare(strict_types=1);

namespace TodoAcesso\Core;

final class Response
{
    public static function redirect(string $location, int $code = 302): never
    {
        // Solo rutas internas o URLs absolutas del propio sitio.
        if (!str_starts_with($location, '/') || str_starts_with($location, '//')) {
            $base = app_url();
            if (!str_starts_with($location, $base)) {
                $location = '/';
            }
        }
        header('Location: ' . str_replace(["\r", "\n"], '', $location), true, $code);
        exit;
    }

    public static function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        exit;
    }

    public static function html(string $html, int $code = 200, bool $cacheable = true): never
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        if (!$cacheable) {
            header('Cache-Control: no-store');
        }
        echo $html;
        exit;
    }

    public static function text(string $body, string $contentType, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: ' . $contentType);
        echo $body;
        exit;
    }

    public static function notFound(Request $req): never
    {
        View::error($req, 404, 'Página no encontrada', 'No encontramos lo que buscabas. Quizás el enlace cambió o el servicio todavía no está en Todo Acesso.');
    }
}
