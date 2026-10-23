<?php
declare(strict_types=1);

namespace TodoAcceso\Core;

/** Sesión administrativa segura + CSRF + protección contra fuerza bruta. */
final class Auth
{
    private const IDLE = 7200;      // 2 h de inactividad
    private const ABSOLUTE = 43200; // 12 h máximo

    public static function start(Request $req): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_name('TA_ADMIN');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/admin',
            'secure' => $req->isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
        $now = time();
        if (isset($_SESSION['uid'])) {
            if ($now - ($_SESSION['last'] ?? 0) > self::IDLE || $now - ($_SESSION['born'] ?? 0) > self::ABSOLUTE) {
                self::destroy();
                session_start();
            } else {
                $_SESSION['last'] = $now;
            }
        }
    }

    public static function check(): bool
    {
        return !empty($_SESSION['uid']);
    }

    public static function require(Request $req): void
    {
        self::start($req);
        if (!self::check()) {
            Response::redirect('/admin/login', 303);
        }
    }

    /** @return string|null mensaje de error o null si el login fue correcto */
    public static function attempt(Request $req, string $user, string $pass): ?string
    {
        $key = 'login:' . $req->clientKey();
        if (RateLimiter::exceeded($key, 5, 900) || RateLimiter::exceeded('login:all', 40, 900)) {
            return 'Demasiados intentos. Esperá 15 minutos antes de volver a intentar.';
        }
        $expectedUser = Env::str('ADMIN_USER');
        $hash = Env::str('ADMIN_PASSWORD_HASH');
        if ($expectedUser === '' || $hash === '') {
            return 'El acceso administrativo no está configurado.';
        }
        // Se ejecuta siempre password_verify para no filtrar por tiempo si el usuario existe.
        $passOk = password_verify($pass, $hash);
        $userOk = hash_equals($expectedUser, $user);
        if ($userOk && $passOk) {
            RateLimiter::clear($key);
            session_regenerate_id(true);
            $_SESSION['uid'] = 1;
            $_SESSION['born'] = time();
            $_SESSION['last'] = time();
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            return null;
        }
        RateLimiter::hit($key, 5, 900);
        RateLimiter::hit('login:all', 40, 900);
        Logger::info('admin_login_failed', ['client' => substr($req->clientKey(), 0, 8)]);
        return 'Usuario o contraseña incorrectos.';
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(Request $req): void
    {
        $sent = $req->p('_csrf');
        if ($sent === '' || !hash_equals(self::csrfToken(), $sent)) {
            View::error($req, 419, 'Sesión vencida', 'El formulario venció o no es válido. Volvé a intentar.');
        }
    }

    public static function flash(?string $message = null, string $tone = 'ok'): ?array
    {
        if ($message !== null) {
            $_SESSION['flash'] = [$tone, $message];
            return null;
        }
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }
}
