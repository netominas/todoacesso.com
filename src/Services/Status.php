<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

/** Estados posibles de una verificación y su texto en español rioplatense. */
final class Status
{
    public const OK = 'ok';
    public const SLOW = 'slow';
    public const RESPONDS = 'responds';
    public const SERVER_ERROR = 'server_error';
    public const DOWN = 'down';
    public const UNKNOWN = 'unknown';

    private const META = [
        self::OK => ['Operativo', 'El servidor respondió con normalidad', 'ok', '✔'],
        self::SLOW => ['Responde lento', 'El servidor respondió, pero más lento de lo habitual', 'warn', '◔'],
        self::RESPONDS => ['El servidor responde', 'El servidor respondió con un código de aviso; suele ser una restricción a verificaciones automáticas y no una caída', 'info', '●'],
        self::SERVER_ERROR => ['Error del servidor', 'El servidor respondió con un error interno', 'bad', '▲'],
        self::DOWN => ['Sin respuesta', 'No pudimos conectarnos desde nuestro servidor', 'bad', '✖'],
        self::UNKNOWN => ['Sin verificar', 'Todavía no verificamos este servicio', 'idle', '○'],
    ];

    public static function label(?string $status): string
    {
        return self::META[$status ?? self::UNKNOWN][0] ?? self::META[self::UNKNOWN][0];
    }

    public static function summary(?string $status): string
    {
        return self::META[$status ?? self::UNKNOWN][1] ?? self::META[self::UNKNOWN][1];
    }

    /** ok | warn | bad | idle (clase CSS) */
    public static function tone(?string $status): string
    {
        return self::META[$status ?? self::UNKNOWN][2] ?? 'idle';
    }

    public static function icon(?string $status): string
    {
        return self::META[$status ?? self::UNKNOWN][3] ?? '○';
    }

    public static function errorText(?string $error): string
    {
        return match ($error) {
            'timeout' => 'se agotó el tiempo de espera',
            'dns' => 'el dominio no se pudo resolver por DNS',
            'connect' => 'la conexión fue rechazada o no se pudo establecer',
            'ssl' => 'hubo un problema con el certificado de seguridad (HTTPS)',
            'blocked' => 'la dirección no es válida para verificar',
            'redirects' => 'hay demasiadas redirecciones',
            default => 'no se pudo completar la conexión',
        };
    }
}
