<?php
declare(strict_types=1);

use TodoAcesso\Core\Env;

const TZ_AR = 'America/Argentina/Buenos_Aires';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return rtrim(Env::str('APP_URL', 'http://localhost:8080'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . $path;
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return '/assets/' . $path . '?v=' . $v;
}

function upload_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    $file = BASE_PATH . '/public/uploads/' . $path;
    if (!is_file($file)) {
        return null;
    }
    return '/uploads/' . $path . '?v=' . filemtime($file);
}

function service_path(array $service): string
{
    return '/estado/' . $service['slug'];
}

function fmt_datetime(?int $ts): string
{
    if (!$ts) {
        return '—';
    }
    $dt = (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone(TZ_AR));
    return $dt->format('d/m/Y H:i') . ' (hora de Argentina)';
}

function fmt_time(int $ts): string
{
    return (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone(TZ_AR))->format('H:i');
}

function time_ago(?int $ts): string
{
    if (!$ts) {
        return 'sin verificar';
    }
    $diff = max(0, time() - $ts);
    if ($diff < 60) {
        return 'hace instantes';
    }
    if ($diff < 3600) {
        $m = intdiv($diff, 60);
        return 'hace ' . $m . ($m === 1 ? ' minuto' : ' minutos');
    }
    if ($diff < 86400) {
        $h = intdiv($diff, 3600);
        return 'hace ' . $h . ($h === 1 ? ' hora' : ' horas');
    }
    $d = intdiv($diff, 86400);
    return 'hace ' . $d . ($d === 1 ? ' día' : ' días');
}

/** Ícono del servicio: imagen local o, si no hay, un círculo con la inicial. */
function service_icon(array $service, int $size = 48, bool $lazy = true): string
{
    $url = upload_url($service['icon_path'] ?? null);
    if ($url !== null) {
        return '<img class="svc-icon" src="' . e($url) . '" width="' . $size . '" height="' . $size . '" alt="Ícono de ' . e($service['name']) . '"'
            . ($lazy ? ' loading="lazy" decoding="async"' : '') . '>';
    }
    $hue = crc32((string) $service['domain']) % 360;
    $initial = mb_strtoupper(mb_substr((string) $service['name'], 0, 1));
    return '<span class="svc-icon svc-icon--initial" style="width:' . $size . 'px;height:' . $size . 'px;--h:' . $hue . '" aria-hidden="true">' . e($initial) . '</span>';
}

function status_pill(?string $status): string
{
    $tone = \TodoAcesso\Services\Status::tone($status);
    return '<span class="pill pill--' . $tone . '"><span aria-hidden="true">' . \TodoAcesso\Services\Status::icon($status) . '</span> '
        . e(\TodoAcesso\Services\Status::label($status)) . '</span>';
}

function json_ld(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
}

function str_limit(string $text, int $max): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $max * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.;:-") . '…';
}
