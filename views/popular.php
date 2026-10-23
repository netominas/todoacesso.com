<?php /** @var array $rows @var int $total @var int $page @var int $pages @var int $perPage @var array $crumbs */ ?>
<div class="wrap">
  <?= \TodoAcceso\Core\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <header class="page-head">
    <h1>Servicios más consultados en Argentina</h1>
    <p class="lead">Ranking según la cantidad de búsquedas en Todo Acceso. Elegí un servicio para ver su estado actual, el gráfico de reportes y el mapa de actividad.</p>
  </header>

  <?php if (!$rows): ?>
    <p class="empty">Todavía no hay servicios en el ranking.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="rank">
      <caption class="sr-only">Servicios ordenados por cantidad de consultas</caption>
      <thead>
        <tr><th scope="col">#</th><th scope="col">Servicio</th><th scope="col">Dominio</th><th scope="col">Estado reciente</th><th scope="col" class="num">Consultas</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $s): $pos = ($page - 1) * $perPage + $i + 1; ?>
        <tr>
          <td data-label="Posición"><?= $pos ?></td>
          <td data-label="Servicio"><a class="rank__name" href="<?= e(service_path($s)) ?>"><?= service_icon($s, 32) ?> <span><?= e($s['name']) ?></span></a></td>
          <td data-label="Dominio"><?= e($s['domain']) ?></td>
          <td data-label="Estado"><?= status_pill($s['last_status']) ?></td>
          <td data-label="Consultas" class="num"><?= number_format((int) $s['search_count'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Paginación">
    <?php if ($page > 1): ?><a class="btn btn--ghost" href="/servicios-populares<?= $page > 2 ? '?pagina=' . ($page - 1) : '' ?>" rel="prev">← Anterior</a><?php endif; ?>
    <span>Página <?= $page ?> de <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn--ghost" href="/servicios-populares?pagina=<?= $page + 1 ?>" rel="next">Siguiente →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
  <p class="note">El ranking refleja el interés de los visitantes y es independiente de la calidad o el estado de cada servicio. El estado mostrado corresponde a la última verificación.</p>
</div>
