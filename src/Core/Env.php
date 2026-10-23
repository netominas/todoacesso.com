<?php
declare(strict_types=1);

namespace TodoAcesso\Core;

final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                $end = strrpos($value, $quote);
                $value = $end > 0 ? substr($value, 1, $end - 1) : substr($value, 1);
            } else {
                $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
            }
            self::$vars[$key] = $value;
        }
    }

    public static function str(string $key, string $default = ''): string
    {
        // Las variables reales del entorno tienen prioridad sobre .env (como en dotenv).
        $env = getenv($key);
        if ($env !== false) {
            return $env;
        }
        return self::$vars[$key] ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::str($key, '');
        return $v !== '' && is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = strtolower(self::str($key, ''));
        if ($v === '') {
            return $default;
        }
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    public static function isLocal(): bool
    {
        return self::str('APP_ENV', 'production') === 'local';
    }
}
