<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

use PDO;

/**
 * Migraciones idempotentes. El número de versión se guarda en app_meta.
 * Cada migración usa CREATE ... IF NOT EXISTS, por lo que re-ejecutarla es seguro.
 */
final class Migrator
{
    public static function run(PDO $pdo): int
    {
        $driver = Database::driver();
        $pdo->exec(self::sql('CREATE TABLE IF NOT EXISTS app_meta (
            meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
            meta_value TEXT NULL
        ) {ENGINE}', $driver));

        $current = (int) (Database::value('SELECT meta_value FROM app_meta WHERE meta_key = ?', ['schema_version']) ?? 0);
        $migrations = require BASE_PATH . '/database/migrations.php';
        ksort($migrations);
        $applied = 0;
        foreach ($migrations as $version => $statements) {
            if ($version <= $current) {
                continue;
            }
            foreach ($statements as $stmt) {
                self::execIdempotent($pdo, self::sql($stmt, $driver));
            }
            Database::run(
                $driver === 'mysql'
                    ? 'REPLACE INTO app_meta (meta_key, meta_value) VALUES (?, ?)'
                    : 'INSERT OR REPLACE INTO app_meta (meta_key, meta_value) VALUES (?, ?)',
                ['schema_version', (string) $version]
            );
            $applied++;
        }
        return $applied;
    }

    private static function sql(string $sql, string $driver): string
    {
        $map = $driver === 'mysql'
            ? ['{PK}' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', '{ENGINE}' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', '{INT}' => 'INT UNSIGNED', '{BIGINT}' => 'BIGINT UNSIGNED']
            : ['{PK}' => 'INTEGER PRIMARY KEY AUTOINCREMENT', '{ENGINE}' => '', '{INT}' => 'INTEGER', '{BIGINT}' => 'INTEGER'];
        if ($driver === 'mysql') {
            // MySQL no soporta IF NOT EXISTS en CREATE INDEX; execIdempotent tolera "duplicate key name".
            $sql = (string) preg_replace('/^CREATE (UNIQUE )?INDEX IF NOT EXISTS/', 'CREATE $1INDEX', $sql);
        }
        return strtr($sql, $map);
    }

    /** ADD COLUMN y CREATE INDEX no tienen IF NOT EXISTS en todos los motores: se toleran "ya existe". */
    private static function execIdempotent(PDO $pdo, string $sql): void
    {
        try {
            $pdo->exec($sql);
        } catch (\PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'duplicate column') || str_contains($msg, 'already exists') || str_contains($msg, 'duplicate key name')) {
                return;
            }
            throw $e;
        }
    }
}
