<?php
/** @var array $seo @var string $content @var \TodoAcceso\Core\Request $req @var string $bodyClass @var bool $needsMap */
use TodoAcceso\Services\Seo;

$gtm = Seo::gtmId();
$title = $seo['title'] ?? 'Todo Acceso';
$desc = $seo['description'] ?? '';
$robots = $seo['robots'] ?? 'index,follow';
$canonical = $seo['canonical'] ?? null;
$image = $seo['image'] ?? Seo::defaultImage();
$isHome = str_contains($bodyClass, 'is-home');
?><!doctype html>
<html lang="es-AR">
<head>
<?php if ($gtm): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtm) ?>');</script>
<!-- End Google Tag Manager -->
<?php endif; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<?php if ($canonical): ?><link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
<?php if (!empty($seo['prev'])): ?><link rel="prev" href="<?= e($seo['prev']) ?>">
<?php endif; ?>
<?php if (!empty($seo['next'])): ?><link rel="next" href="<?= e($seo['next']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#0b3a8c">
<meta property="og:site_name" content="Todo Acceso">
<meta property="og:locale" content="es_AR">
<meta property="og:type" content="<?= e($seo['type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<?php if ($canonical): ?><meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<meta property="og:image" content="<?= e($image) ?>">
<?php if (!empty($seo['image_alt'])): ?><meta property="og:image:alt" content="<?= e($seo['image_alt']) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<meta name="twitter:image" content="<?= e($image) ?>">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/assets/img/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<?php if (!empty($seo['jsonld'])): ?><script type="application/ld+json"><?= $seo['jsonld'] ?></script>
<?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($gtm): ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php endif; ?>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<header class="site-header">
  <div class="wrap site-header__row">
    <a class="brand" href="/" aria-label="Todo Acceso, ir al inicio">
      <svg class="brand__mark" width="36" height="36" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><rect width="40" height="40" rx="11" fill="#0b3a8c"/><path d="M6 22h7l3-8 5 16 3-8h10" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="31.5" cy="9.5" r="3.5" fill="#f5b400"/></svg>
      <span class="brand__name">Todo <b>Acceso</b></span>
    </a>
    <nav class="main-nav" aria-label="Principal">
      <a href="/servicios-populares">Servicios populares</a>
      <a href="/#como-funciona">Cómo funciona</a>
      <a href="/sobre-nosotros">Sobre nosotros</a>
    </nav>
  </div>
  <?php if (!$isHome): ?>
  <div class="wrap header-search">
    <?= \TodoAcceso\Core\View::partial('partials/search_form', ['id' => 'q-header', 'compact' => true, 'query' => '']) ?>
  </div>
  <?php endif; ?>
</header>

<main id="contenido" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div>
      <a class="brand brand--footer" href="/"><span class="brand__name">Todo <b>Acceso</b></span></a>
      <p class="muted">Herramienta independiente para comprobar si sitios y aplicaciones funcionan en Argentina, con reportes de la comunidad.</p>
    </div>
    <nav aria-label="Servicios">
      <h2 class="footer-title">Consultar estado</h2>
      <ul class="plain">
        <li><a href="/estado/whatsapp-com">¿WhatsApp está caído?</a></li>
        <li><a href="/estado/instagram-com">¿Instagram no funciona?</a></li>
        <li><a href="/estado/chatgpt-com">¿ChatGPT está caído?</a></li>
        <li><a href="/estado/mercadolibre-com-ar">Mercado Libre</a></li>
        <li><a href="/servicios-populares">Ver todos los servicios</a></li>
      </ul>
    </nav>
    <nav aria-label="Información">
      <h2 class="footer-title">Información</h2>
      <ul class="plain">
        <li><a href="/sobre-nosotros">Sobre Todo Acceso</a></li>
        <li><a href="/privacidad">Política de privacidad</a></li>
        <li><a href="/terminos">Términos y condiciones</a></li>
      </ul>
    </nav>
  </div>
  <div class="wrap footer-legal">
    <p>Todo Acceso es un sitio independiente y no está afiliado, asociado ni patrocinado por las marcas, empresas u organismos mencionados. Los nombres y logotipos pertenecen a sus respectivos titulares. La información es orientativa.</p>
    <p>© <?= date('Y') ?> Todo Acceso · Argentina</p>
  </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
