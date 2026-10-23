<?php
declare(strict_types=1);

// Genera los PNG de marca (favicon, apple-touch-icon, logo y og-default) con GD.
// Uso: php bin/make-brand-assets.php "C:\Windows\Fonts\segoeuib.ttf"  (o cualquier TTF bold)
$font = $argv[1] ?? '';
if (!is_file($font)) {
    fwrite(STDERR, "Indicá la ruta de una fuente TTF bold.\n");
    exit(1);
}
$out = dirname(__DIR__) . '/public/assets/img';
@mkdir($out, 0755, true);

function mark(int $size): GdImage
{
    $s = 4; // supermuestreo para suavizar
    $big = $size * $s;
    $im = imagecreatetruecolor($big, $big);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
    imagealphablending($im, true);
    $blue = imagecolorallocate($im, 11, 58, 140);
    $white = imagecolorallocate($im, 255, 255, 255);
    $sun = imagecolorallocate($im, 245, 180, 0);
    $r = (int) ($big * 0.275);
    imagefilledrectangle($im, $r, 0, $big - $r, $big, $blue);
    imagefilledrectangle($im, 0, $r, $big, $big - $r, $blue);
    foreach ([[$r, $r], [$big - $r, $r], [$r, $big - $r], [$big - $r, $big - $r]] as [$cx, $cy]) {
        imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $blue);
    }
    // línea de pulso (coordenadas sobre 40x40)
    $pts = [[6, 22], [13, 22], [16, 14], [21, 30], [24, 22], [34, 22]];
    imagesetthickness($im, (int) ($big * 0.075));
    for ($i = 0; $i < count($pts) - 1; $i++) {
        imageline($im, (int) ($pts[$i][0] / 40 * $big), (int) ($pts[$i][1] / 40 * $big), (int) ($pts[$i + 1][0] / 40 * $big), (int) ($pts[$i + 1][1] / 40 * $big), $white);
    }
    $dot = (int) ($big * 0.075);
    foreach ($pts as [$x, $y]) {
        imagefilledellipse($im, (int) ($x / 40 * $big), (int) ($y / 40 * $big), $dot, $dot, $white);
    }
    imagefilledellipse($im, (int) (31.5 / 40 * $big), (int) (9.5 / 40 * $big), (int) ($big * 0.175), (int) ($big * 0.175), $sun);
    $res = imagecreatetruecolor($size, $size);
    imagealphablending($res, false);
    imagesavealpha($res, true);
    imagefill($res, 0, 0, imagecolorallocatealpha($res, 0, 0, 0, 127));
    imagecopyresampled($res, $im, 0, 0, 0, 0, $size, $size, $big, $big);
    return $res;
}

foreach (['favicon-32.png' => 32, 'apple-touch-icon.png' => 180, 'logo-512.png' => 512] as $file => $size) {
    imagepng(mark($size), "$out/$file", 9);
}

// og-default 1200x630
$w = 1200; $h = 630;
$im = imagecreatetruecolor($w, $h);
for ($y = 0; $y < $h; $y++) {
    $t = $y / $h;
    $c = imagecolorallocate($im, (int) (7 + (27 - 7) * $t), (int) (26 + (120 - 26) * $t), (int) (61 + (209 - 61) * $t));
    imageline($im, 0, $y, $w, $y, $c);
}
for ($i = 3; $i >= 1; $i--) {
    imagesetthickness($im, 2);
    imageellipse($im, 1080, 80, (int) (420 * $i / 1.5), (int) (420 * $i / 1.5),imagecolorallocatealpha($im, 255, 255, 255, 112));
}
$mk = mark(150);
imagecopy($im, $mk, 80, 90, 0, 0, 150, 150);
$white = imagecolorallocate($im, 255, 255, 255);
$sky = imagecolorallocate($im, 140, 202, 245);
imagettftext($im, 64, 0, 260, 190, $white, $font, 'Todo Acceso');
imagettftext($im, 46, 0, 80, 360, $white, $font, '¿Un sitio o una app no funciona?');
imagettftext($im, 46, 0, 80, 430, $sky, $font, 'Comprobá su estado en segundos.');
imagettftext($im, 26, 0, 80, 550, $white, $font, 'Estado de sitios y apps en Argentina · todoacesso.com');
imagepng($im, "$out/og-default.png", 9);
echo "Listo.\n";
