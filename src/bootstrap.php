<?php
declare(strict_types=1);

use TodoAcceso\Core\Database;
use TodoAcceso\Core\Env;
use TodoAcceso\Core\Logger;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'TodoAcceso\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/helpers.php';

Env::load(BASE_PATH . '/.env');

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

$debug = Env::bool('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

if (Env::bool('APP_AUTO_MIGRATE', false)) {
    try {
        \TodoAcceso\Core\Migrator::run(Database::pdo());
    } catch (Throwable $e) {
        Logger::error('migration_failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}
