<?php
/** @var array $seo @var string $content @var string $bodyClass */
use TodoAcceso\Core\Auth;
?><!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($seo['title'] ?? 'Admin') ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if (Auth::check()): ?>
<header class="admin-bar">
  <div class="wrap">
    <strong>Todo Acceso · Admin</strong>
    <a href="/admin">Panel</a>
    <a href="/admin/servicios">Servicios</a>
    <a href="/admin/destacados">Destacados</a>
    <a href="/" target="_blank" rel="noopener">Ver sitio ↗</a>
    <form method="post" action="/admin/logout"><?= Auth::csrfField() ?><button type="submit">Salir</button></form>
  </div>
</header>
<?php endif; ?>
<main class="wrap admin-main">
<?= $content ?>
</main>
</body>
</html>
