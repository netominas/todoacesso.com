<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

final class Router
{
    /** @var array<int,array{0:string,1:string,2:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(Request $req): void
    {
        $allowed = [];
        foreach ($this->routes as [$method, $regex, $handler]) {
            if (!preg_match($regex, $req->path, $m)) {
                continue;
            }
            if ($method !== $req->method && !($method === 'GET' && $req->method === 'HEAD')) {
                $allowed[] = $method;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($req, ...array_values($params));
            return;
        }
        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            View::error($req, 405, 'Método no permitido', 'Esta dirección no acepta ese tipo de solicitud.');
        }
        Response::notFound($req);
    }
}
