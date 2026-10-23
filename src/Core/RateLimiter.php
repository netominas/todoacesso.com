<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

/** Limitador de ventana fija almacenado en la base de datos. */
final class RateLimiter
{
    /** Registra un intento. Devuelve false si se superó el límite. */
    public static function hit(string $key, int $limit, int $windowSeconds): bool
    {
        $key = substr($key, 0, 100);
        $now = time();
        $row = Database::one('SELECT window_start, hits FROM rate_limits WHERE rl_key = ?', [$key]);
        if ($row === null || $now - (int) $row['window_start'] >= $windowSeconds) {
            Database::run(
                Database::driver() === 'mysql'
                    ? 'REPLACE INTO rate_limits (rl_key, window_start, hits) VALUES (?, ?, 1)'
                    : 'INSERT OR REPLACE INTO rate_limits (rl_key, window_start, hits) VALUES (?, ?, 1)',
                [$key, $now]
            );
            return true;
        }
        if ((int) $row['hits'] >= $limit) {
            return false;
        }
        Database::run('UPDATE rate_limits SET hits = hits + 1 WHERE rl_key = ?', [$key]);
        return true;
    }

    /** ¿Se alcanzó ya el límite? (no suma intentos) */
    public static function exceeded(string $key, int $limit, int $windowSeconds): bool
    {
        $row = Database::one('SELECT window_start, hits FROM rate_limits WHERE rl_key = ?', [substr($key, 0, 100)]);
        if ($row === null || time() - (int) $row['window_start'] >= $windowSeconds) {
            return false;
        }
        return (int) $row['hits'] >= $limit;
    }

    public static function clear(string $key): void
    {
        Database::run('DELETE FROM rate_limits WHERE rl_key = ?', [substr($key, 0, 100)]);
    }

    public static function prune(int $olderThanSeconds = 86400): int
    {
        return Database::run('DELETE FROM rate_limits WHERE window_start < ?', [time() - $olderThanSeconds]);
    }
}
