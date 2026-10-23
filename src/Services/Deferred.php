<?php
declare(strict_types=1);

namespace TodoAcesso\Services;

use TodoAcesso\Core\Logger;

/** Ejecuta tareas pesadas (descarga de imágenes) después de enviar la respuesta al visitante. */
final class Deferred
{
    /** @var array<int,callable> */
    private static array $tasks = [];
    private static bool $registered = false;

    public static function after(callable $task): void
    {
        self::$tasks[] = $task;
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        register_shutdown_function(static function (): void {
            ignore_user_abort(true);
            @set_time_limit(60);
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            foreach (self::$tasks as $task) {
                try {
                    $task();
                } catch (\Throwable $e) {
                    Logger::error('deferred_failed', ['error' => $e->getMessage()]);
                }
            }
        });
    }
}
