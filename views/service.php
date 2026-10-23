<?php
/**
 * @var array $service @var array $content @var array $activity @var ?string $iconUrl @var ?string $heroUrl @var ?array $heroDims
 * @var array $related @var array $crumbs @var array $problemTypes @var string $token @var ?array $flash @var ?string $flashKey
 * @var bool $stale @var string $categoryLabel
 */
use TodoAcceso\Core\View;

$slug = $service['slug'];
$name = $service['name'];
$official = 'https://' . $service['domain'] . '/';
?>
<div class="wrap">
  <?= View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>

  <header class="svc-head">
    <div class="svc-head__id">
      <?= service_icon($service, 64, false) ?>
      <div>
        <p class="eyebrow"><?= e($categoryLabel) ?></p>
        <h1><?= e($content['h1']) ?></h1>
        <p class="svc-head__domain">Dominio verificado: <strong><?= e($service['domain']) ?></strong></p>
      </div>
    </div>
    <?php if ($heroUrl): ?>
      <img class="svc-head__hero" src="<?= e($heroUrl) ?>" alt="Imagen de portada de <?= e($name) ?> (obtenida del sitio oficial)"
           width="<?= (int) ($heroDims[0] ?? 1200) ?>" height="<?= (int) ($heroDims[1] ?? 630) ?>" fetchpriority="high" decoding="async">
    <?php endif; ?>
  </header>

  <?php if (!empty($service['description'])): ?>
    <p class="lead svc-desc"><?= e($service['description']) ?></p>
  <?php endif; ?>

  <section class="status-wrap" aria-labelledby="estado-title" data-status-root data-slug="<?= e($slug) ?>" data-token="<?= e($token) ?>" data-stale="<?= $stale ? '1' : '0' ?>">
    <h2 id="estado-title" class="sr-only">Estado de <?= e($name) ?></h2>
    <div id="status-fragment" aria-live="polite"><?= View::partial('partials/status_card', ['service' => $service, 'activity' => $activity, 'stale' => $stale]) ?></div>
    <div class="actions">
      <a class="btn btn--primary" href="<?= e($official) ?>" target="_blank" rel="noopener noreferrer nofollow">Abrir el sitio oficial de <?= e($name) ?> <span aria-hidden="true">↗</span></a>
      <form method="post" action="/estado/<?= e($slug) ?>/verificar" data-recheck-form>
        <input type="hidden" name="_t" value="<?= e($token) ?>">
        <button class="btn btn--ghost" type="submit"><span aria-hidden="true">↻</span> Verificar de nuevo</button>
      </form>
    </div>
    <p class="note">La verificación se hace desde nuestro servidor, en un momento puntual. Que el servidor responda no garantiza que todo funcione, y la disponibilidad puede variar según tu ciudad, operadora o dispositivo.</p>
  </section>

  <div class="two-col">
    <section class="card" id="reportar" aria-labelledby="reportar-title">
      <h2 id="reportar-title">¿Tenés problemas con <?= e($name) ?>?</h2>
      <p class="muted">Contanos qué te pasa. No hace falta registrarse.</p>
      <div id="report-message" class="alert alert--<?= $flash ? e($flash[0]) : 'hidden' ?><?= $flash ? '' : ' is-hidden' ?>" role="status" aria-live="polite"><?= $flash ? e($flash[1]) : '' ?></div>
      <form method="post" action="/estado/<?= e($slug) ?>/reportar" data-report-form>
        <fieldset class="problems">
          <legend class="sr-only">Tipo de problema</legend>
          <?php foreach ($problemTypes as $key => $p): ?>
            <label class="problem">
              <input type="radio" name="problem" value="<?= e($key) ?>" required>
              <span class="problem__box"><strong><?= e($p['label']) ?></strong><small><?= e($p['hint']) ?></small></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
        <input type="hidden" name="_t" value="<?= e($token) ?>">
        <input type="hidden" name="lat" value="">
        <input type="hidden" name="lon" value="">
        <div class="hp" aria-hidden="true"><label>No completar este campo <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="btn btn--alert btn--block" type="submit" data-report-btn>Informar problema</button>
        <p class="note">Al enviar, tu navegador puede pedirte permiso para compartir tu ubicación. Es <strong>voluntario</strong>: si no la compartís, el reporte se envía igual. Guardamos solo una ubicación aproximada y nunca mostramos puntos individuales. <a href="/privacidad#ubicacion">Más información</a>.</p>
      </form>
    </section>

    <section class="card" aria-labelledby="dist-title">
      <h2 id="dist-title">Problemas reportados (24 h)</h2>
      <div id="breakdown-fragment" aria-live="polite"><?= View::partial('partials/breakdown', ['activity' => $activity]) ?></div>
    </section>
  </div>

  <section class="card card--wide" aria-labelledby="chart-title">
    <h2 id="chart-title">Reportes de las últimas 24 horas</h2>
    <div id="chart-fragment"><?= View::partial('partials/chart', ['activity' => $activity, 'service' => $service]) ?></div>
  </section>

  <section class="card card--wide" id="mapa" aria-labelledby="mapa-title">
    <h2 id="mapa-title">Mapa de reportes en Argentina</h2>
    <p class="muted">Cada círculo agrupa los reportes de las últimas 24 horas de una ciudad y su zona de influencia. Los puntos son aproximados y agregados: nunca se muestra la posición de una persona.</p>
    <div class="map-box">
      <div id="map" class="map" data-css="<?= e(asset('vendor/leaflet/leaflet.css')) ?>" data-js="<?= e(asset('vendor/leaflet/leaflet.js')) ?>" data-regions="<?= e(json_encode($activity['regions'], JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Mapa de reportes por zona en Argentina"></div>
      <div id="map-empty" class="map-empty<?= $activity['regions'] ? ' is-hidden' : '' ?>">
        <p><strong>Todavía no hay reportes con ubicación.</strong><br>Cuando alguien informe un problema y comparta su ubicación aproximada, la zona aparecerá acá.</p>
      </div>
    </div>
    <ul id="regions-list" class="regions"<?= $activity['regions'] ? '' : ' hidden' ?>>
      <?php foreach ($activity['regions'] as $r): ?><li><?= e($r['name']) ?>: <strong><?= (int) $r['count'] ?></strong></li><?php endforeach; ?>
    </ul>
    <noscript><p class="note">El mapa interactivo necesita JavaScript. La lista de zonas con reportes aparece arriba.</p></noscript>
  </section>

  <article class="prose editorial" aria-label="Información sobre <?= e($name) ?>">
    <h2>¿Qué es <?= e($name) ?>?</h2>
    <?php foreach ($content['about'] as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>

    <h2>¿Para qué se usa?</h2>
    <ul><?php foreach ($content['uses'] as $u): ?><li><?= e($u) ?></li><?php endforeach; ?></ul>

    <h2>Principales plataformas</h2>
    <ul><?php foreach ($content['platforms'] as $u): ?><li><?= e($u) ?></li><?php endforeach; ?></ul>

    <h2>Problemas más comunes con <?= e($name) ?></h2>
    <dl class="defs">
      <?php foreach ($content['problems'] as [$t, $d]): ?><dt><?= e($t) ?></dt><dd><?= e($d) ?></dd><?php endforeach; ?>
    </dl>

    <h2>¿Cómo saber si la falla es general?</h2>
    <p>Combiná tres señales: el estado técnico de arriba, el gráfico de reportes frente a la actividad habitual y el mapa. Si las tres apuntan a un problema, es probable que la falla sea general. Si solo vos tenés el inconveniente, seguí los pasos de la siguiente sección.</p>
    <div class="table-wrap">
      <table class="compare">
        <caption class="sr-only">Diferencias entre una falla local y una caída general</caption>
        <thead><tr><th scope="col">Aspecto</th><th scope="col">Falla local</th><th scope="col">Caída general</th></tr></thead>
        <tbody>
          <?php foreach ($content['local_vs_general'] as [$a, $l, $g]): ?>
            <tr><th scope="row"><?= e($a) ?></th><td><?= e($l) ?></td><td><?= e($g) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <h2>Cómo probar la conexión, el DNS, la aplicación y el dispositivo</h2>
    <ol class="checklist">
      <?php foreach ($content['troubleshooting'] as [$t, $d]): ?><li><strong><?= e($t) ?>.</strong> <?= e($d) ?></li><?php endforeach; ?>
    </ol>

    <h2>Cómo interpretar los reportes</h2>
    <ul><?php foreach ($content['interpret'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul>
    <?php if (!empty($content['official_note'])): ?><p class="note"><?= e($content['official_note']) ?></p><?php endif; ?>
  </article>

  <section class="prose faq" aria-labelledby="faq-title">
    <h2 id="faq-title">Preguntas frecuentes sobre <?= e($name) ?></h2>
    <?php foreach ($content['faq'] as [$q, $a]): ?>
      <details>
        <summary><?= e($q) ?></summary>
        <p><?= e($a) ?></p>
      </details>
    <?php endforeach; ?>
  </section>

  <aside class="notice" aria-label="Aviso de independencia">
    <strong>Aviso de independencia.</strong> Todo Acceso es un sitio independiente y no está afiliado, asociado ni patrocinado por <?= e($name) ?> ni por su titular. El nombre y el logotipo pertenecen a sus respectivos dueños y se muestran solo para identificar el servicio consultado. Para soporte o información oficial, visitá <a href="<?= e($official) ?>" target="_blank" rel="noopener noreferrer nofollow">el sitio oficial</a>.
  </aside>

  <?php if ($related): ?>
  <section class="section-pad" aria-labelledby="rel-title">
    <h2 id="rel-title">Otros servicios que podés consultar</h2>
    <div class="svc-grid">
      <?php foreach ($related as $s): ?><?= View::partial('partials/service_card', ['s' => $s]) ?><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
