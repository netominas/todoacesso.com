<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

use TodoAcceso\Core\Logger;

/**
 * Descarga, valida y normaliza imágenes públicas (favicon / og:image) o subidas manuales.
 * Todo se re-codifica con GD: se descarta cualquier contenido activo o metadatos.
 * SVG se rechaza. Las marcas no se deforman (se conserva la proporción).
 */
final class ImageImporter
{
    private const ICON_MAX_BYTES = 524288;   // 512 KB
    private const HERO_MAX_BYTES = 3145728;  // 3 MB
    private const MAX_PIXELS = 16000000;
    private const ALLOWED = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];

    /** @param array<int,string> $iconCandidates */
    public static function importForService(array $service, array $iconCandidates, ?string $heroUrl, bool $force = false, bool $overwriteManual = false): void
    {
        $fields = [];
        $hasIcon = !empty($service['icon_path']) && is_file(BASE_PATH . '/public/uploads/' . $service['icon_path']);
        $iconManual = ($service['icon_source_url'] ?? '') === 'manual';
        if (($force || !$hasIcon) && (!$iconManual || $overwriteManual)) {
            foreach ($iconCandidates as $url) {
                $img = self::download($url, self::ICON_MAX_BYTES);
                $png = $img ? self::normalizeIcon($img) : null;
                if ($png !== null) {
                    $path = self::store($png, 'icons', (string) $service['slug'], 'png');
                    if ($path !== null) {
                        self::removeFile($service['icon_path'] ?? null);
                        $fields['icon_path'] = $path;
                        $fields['icon_source_url'] = $url;
                        break;
                    }
                }
            }
        }

        $hasHero = !empty($service['hero_image_path']) && is_file(BASE_PATH . '/public/uploads/' . $service['hero_image_path']);
        $heroManual = ($service['hero_image_source_url'] ?? '') === 'manual';
        if ($heroUrl !== null && ($force || !$hasHero) && (!$heroManual || $overwriteManual)) {
            $img = self::download($heroUrl, self::HERO_MAX_BYTES);
            $hero = $img ? self::normalizeHero($img) : null;
            if ($hero !== null) {
                $path = self::store($hero['bytes'], 'heroes', (string) $service['slug'], $hero['ext']);
                if ($path !== null) {
                    self::removeFile($service['hero_image_path'] ?? null);
                    $fields['hero_image_path'] = $path;
                    $fields['hero_image_source_url'] = $heroUrl;
                }
            }
        }
        if ($fields) {
            ServiceRepository::update((int) $service['id'], $fields);
        }
    }

    /** Subida manual desde el panel. Devuelve [ruta, null] o [null, mensaje de error]. */
    public static function saveUpload(array $service, array $file, string $kind): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [null, 'No se pudo recibir el archivo.'];
        }
        $limit = $kind === 'icon' ? self::ICON_MAX_BYTES * 2 : self::HERO_MAX_BYTES;
        if (($file['size'] ?? 0) <= 0 || $file['size'] > $limit) {
            return [null, 'El archivo supera el tamaño máximo permitido.'];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return [null, 'Subida no válida.'];
        }
        $bytes = (string) file_get_contents($file['tmp_name']);
        if ($kind === 'icon') {
            $png = self::normalizeIcon($bytes);
            if ($png === null) {
                return [null, 'Imagen no válida. Usá PNG, JPG, WebP, GIF o ICO (no SVG).'];
            }
            $path = self::store($png, 'icons', (string) $service['slug'], 'png');
            return $path ? [$path, null] : [null, 'No se pudo guardar la imagen.'];
        }
        $hero = self::normalizeHero($bytes);
        if ($hero === null) {
            return [null, 'Imagen no válida. Usá PNG, JPG, WebP o GIF (mínimo 200 px de ancho, sin SVG).'];
        }
        $path = self::store($hero['bytes'], 'heroes', (string) $service['slug'], $hero['ext']);
        return $path ? [$path, null] : [null, 'No se pudo guardar la imagen.'];
    }

    public static function removeFile(?string $relative): void
    {
        if ($relative === null || $relative === '' || !preg_match('#^(icons|heroes)/[a-z0-9._-]+$#i', $relative)) {
            return;
        }
        $file = BASE_PATH . '/public/uploads/' . $relative;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function download(string $url, int $limit): ?string
    {
        $r = HttpClient::get($url, ['timeout' => 6, 'max_bytes' => $limit + 1, 'max_redirects' => 3, 'accept' => 'image/*,*/*;q=0.5']);
        if (!$r['ok'] || $r['status'] !== 200 || $r['body'] === '' || strlen($r['body']) > $limit) {
            return null;
        }
        return $r['body'];
    }

    private static function mime(string $bytes): string
    {
        $f = new \finfo(FILEINFO_MIME_TYPE);
        return (string) $f->buffer($bytes);
    }

    private static function normalizeIcon(string $bytes): ?string
    {
        $mime = self::mime($bytes);
        if (!in_array($mime, self::ALLOWED, true)) {
            return null;
        }
        if ($mime === 'image/x-icon' || $mime === 'image/vnd.microsoft.icon') {
            $img = self::icoToImage($bytes);
        } else {
            $img = self::decode($bytes);
        }
        if ($img === null) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $side = min(128, max($w, $h));
        $scale = min($side / $w, $side / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $canvas = imagecreatetruecolor($side, $side);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $img, intdiv($side - $nw, 2), intdiv($side - $nh, 2), 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagepng($canvas, null, 9);
        return (string) ob_get_clean();
    }

    /** @return array{bytes:string,ext:string}|null */
    private static function normalizeHero(string $bytes): ?array
    {
        $mime = self::mime($bytes);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            return null;
        }
        $img = self::decode($bytes);
        if ($img === null) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 200 || $h < 100) {
            return null;
        }
        if ($w > 1200) {
            $scaled = imagescale($img, 1200, (int) round($h * 1200 / $w), IMG_BICUBIC);
            if ($scaled !== false) {
                $img = $scaled;
            }
        }
        $usesAlpha = $mime === 'image/png' || $mime === 'image/gif' || $mime === 'image/webp';
        ob_start();
        if ($usesAlpha) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagepng($img, null, 8);
            $ext = 'png';
        } else {
            imagejpeg($img, null, 84);
            $ext = 'jpg';
        }
        return ['bytes' => (string) ob_get_clean(), 'ext' => $ext];
    }

    private static function decode(string $bytes): ?\GdImage
    {
        $info = @getimagesizefromstring($bytes);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }
        $img = @imagecreatefromstring($bytes);
        return $img === false ? null : $img;
    }

    /** Decodifica la entrada más grande de un .ico (PNG embebido o BMP de 4/8/24/32 bits). */
    private static function icoToImage(string $bytes): ?\GdImage
    {
        if (strlen($bytes) < 22 || substr($bytes, 0, 4) !== "\0\0\1\0") {
            return null;
        }
        $count = unpack('v', substr($bytes, 4, 2))[1];
        $best = null;
        $bestSize = 0;
        for ($i = 0; $i < min($count, 16); $i++) {
            $e = substr($bytes, 6 + $i * 16, 16);
            if (strlen($e) < 16) {
                break;
            }
            $w = ord($e[0]) ?: 256;
            $len = unpack('V', substr($e, 8, 4))[1];
            $off = unpack('V', substr($e, 12, 4))[1];
            if ($w > $bestSize && $off < strlen($bytes)) {
                $best = substr($bytes, $off, $len);
                $bestSize = $w;
            }
        }
        if ($best === null) {
            return null;
        }
        if (str_starts_with($best, "\x89PNG")) {
            return self::decode($best);
        }
        return self::decodeIcoBmp($best);
    }

    private static function decodeIcoBmp(string $d): ?\GdImage
    {
        if (strlen($d) < 40) {
            return null;
        }
        $h = unpack('VbiSize/Vwidth/Vheight/vplanes/vbpp/Vcomp/VimgSize/VxPpm/VyPpm/VclrUsed', substr($d, 0, 36));
        $w = (int) $h['width'];
        $ht = intdiv((int) $h['height'], 2);
        $bpp = (int) $h['bpp'];
        if ($h['comp'] !== 0 || $w < 1 || $w > 256 || $ht < 1 || $ht > 256 || !in_array($bpp, [4, 8, 24, 32], true)) {
            return null;
        }
        $palCount = $bpp <= 8 ? ((int) $h['clrUsed'] ?: (1 << $bpp)) : 0;
        $palette = [];
        for ($i = 0; $i < $palCount; $i++) {
            $p = substr($d, $h['biSize'] + $i * 4, 4);
            if (strlen($p) < 4) {
                return null;
            }
            $palette[$i] = [ord($p[2]), ord($p[1]), ord($p[0])];
        }
        $pix = (int) $h['biSize'] + $palCount * 4;
        $rowSize = intdiv($w * $bpp + 31, 32) * 4;
        $maskOff = $pix + $rowSize * $ht;
        $maskRow = intdiv($w + 31, 32) * 4;
        if (strlen($d) < $pix + $rowSize * $ht) {
            return null;
        }
        $img = imagecreatetruecolor($w, $ht);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, 0x7F000000);
        for ($y = 0; $y < $ht; $y++) {
            $row = substr($d, $pix + $rowSize * ($ht - 1 - $y), $rowSize);
            $mask = substr($d, $maskOff + $maskRow * ($ht - 1 - $y), $maskRow);
            for ($x = 0; $x < $w; $x++) {
                $a = 255;
                if ($bpp === 32) {
                    [$b, $g, $r, $a] = [ord($row[$x * 4]), ord($row[$x * 4 + 1]), ord($row[$x * 4 + 2]), ord($row[$x * 4 + 3])];
                } elseif ($bpp === 24) {
                    [$b, $g, $r] = [ord($row[$x * 3]), ord($row[$x * 3 + 1]), ord($row[$x * 3 + 2])];
                } else {
                    $idx = $bpp === 8 ? ord($row[$x]) : (($x % 2) === 0 ? ord($row[intdiv($x, 2)]) >> 4 : ord($row[intdiv($x, 2)]) & 15);
                    [$r, $g, $b] = $palette[$idx] ?? [0, 0, 0];
                }
                if ($bpp !== 32 && $mask !== '' && isset($mask[$x >> 3]) && (ord($mask[$x >> 3]) >> (7 - ($x & 7))) & 1) {
                    $a = 0;
                }
                imagesetpixel($img, $x, $y, ((127 - ($a >> 1)) << 24) | ($r << 16) | ($g << 8) | $b);
            }
        }
        return $img;
    }

    private static function store(string $bytes, string $dir, string $slug, string $ext): ?string
    {
        $folder = BASE_PATH . '/public/uploads/' . $dir;
        if (!is_dir($folder) && !@mkdir($folder, 0755, true) && !is_dir($folder)) {
            Logger::error('upload_dir_failed', ['dir' => $dir]);
            return null;
        }
        $safeSlug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?: 'servicio';
        $name = substr($safeSlug, 0, 60) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (@file_put_contents($folder . '/' . $name, $bytes, LOCK_EX) === false) {
            Logger::error('upload_write_failed', ['dir' => $dir]);
            return null;
        }
        @chmod($folder . '/' . $name, 0644);
        return $dir . '/' . $name;
    }
}
