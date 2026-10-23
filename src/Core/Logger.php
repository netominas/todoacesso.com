<?php
declare(strict_types=1);

namespace TodoAcesso\Core;

/** Log simple a archivo. No se registran IP crudas ni contenido de formularios. */
final class Logger
{
    public static function info(string $event, array $context = []): void
    {
        self::write('INFO', $event, $context);
    }

    public static function error(string $event, array $context = []): void
    {
        self::write('ERROR', $event, $context);
    }

    private static function write(string $level, string $event, array $context): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $line = sprintf(
            "[%s] %s %s %s\n",
            gmdate('Y-m-d H:i:s'),
            $level,
            $event,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );
        if (@file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(trim($line));
        }
    }
}
