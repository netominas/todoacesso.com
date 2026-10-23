<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function driver(): string
    {
        $d = strtolower(Env::str('DB_DRIVER', 'sqlite'));
        return $d === 'mysql' ? 'mysql' : 'sqlite';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if (self::driver() === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Env::str('DB_HOST', '127.0.0.1'),
                Env::int('DB_PORT', 3306),
                Env::str('DB_NAME', 'todoacceso')
            );
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
            self::$pdo = new PDO($dsn, Env::str('DB_USER'), Env::str('DB_PASS'), $options);
            self::$pdo->exec("SET time_zone = '+00:00'");
        } else {
            $path = Env::str('DB_PATH', 'storage/database.sqlite');
            if (!preg_match('#^([a-zA-Z]:)?[\\\\/]#', $path)) {
                $path = BASE_PATH . '/' . $path;
            }
            $dir = dirname($path);
            if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
                throw new RuntimeException('No se pudo crear el directorio de la base de datos.');
            }
            self::$pdo = new PDO('sqlite:' . $path, null, null, $options);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$pdo->exec('PRAGMA synchronous = NORMAL');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }
        return self::$pdo;
    }

    /** Integer division expression compatible with both drivers. */
    public static function intDiv(string $column, int $by): string
    {
        return self::driver() === 'mysql' ? "($column DIV $by)" : "($column / $by)";
    }

    public static function isUniqueViolation(\PDOException $e): bool
    {
        $info = $e->errorInfo;
        if (($info[0] ?? '') === '23000') {
            return true;
        }
        return $e->getCode() === '23000';
    }

    /** @param array<int,mixed> $params */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @param array<int,mixed> $params */
    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<int,mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? null : $v;
    }

    /** @param array<int,mixed> $params */
    public static function run(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }
}
