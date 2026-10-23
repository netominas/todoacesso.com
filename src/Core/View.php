<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

final class View
{
    /** Renderiza una vista dentro del layout. $seo define title/description/canonical/robots/og/jsonld. */
    public static function page(Request $req, string $view, array $data, array $seo, string $layout = 'layout', int $code = 200, bool $cacheable = true): never
    {
        $content = self::partial($view, $data);
        $html = self::partial($layout, [
            'content' => $content,
            'seo' => $seo,
            'req' => $req,
            'bodyClass' => $data['bodyClass'] ?? '',
            'needsMap' => $data['needsMap'] ?? false,
        ]);
        $noindex = str_contains((string) ($seo['robots'] ?? ''), 'noindex');
        Security::applyHeaders($req, $noindex);
        Response::html($html, $code, $cacheable);
    }

    public static function partial(string $name, array $vars = []): string
    {
        $file = BASE_PATH . '/views/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vista inexistente: ' . $name);
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    public static function error(Request $req, int $code, string $title, string $message): never
    {
        $seo = [
            'title' => $title . ' | Todo Acceso',
            'description' => $message,
            'robots' => 'noindex,follow',
            'canonical' => null,
        ];
        self::page($req, 'error', ['code' => $code, 'title' => $title, 'message' => $message], $seo, 'layout', $code, false);
    }
}
