<?php /** @var array $stats @var array $top @var array $pending @var ?array $flash */ ?>
<h1>Panel</h1>
<?php if ($flash): ?><p class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></p><?php endif; ?>
<div class="stat-grid">
  <div class="stat"><b><?= $stats['services'] ?></b>Servicios</div>
  <div class="stat"><b><?= $stats['indexable'] ?></b>Indexables</div>
  <div class="stat"><b><?= $stats['featured'] ?></b>Destacados</div>
  <div class="stat"><b><?= number_format($stats['searches'], 0, ',', '.') ?></b>Búsquedas totales</div>
  <div class="stat"><b><?= $stats['reports_24h'] ?></b>Reportes (24 h)</div>
  <div class="stat"><b><?= $stats['reports_total'] ?></b>Reportes totales</div>
</div>
<div class="two-col">
  <section class="card">
    <h2>Más buscados</h2>
    <div class="table-wrap"><table class="admin-table">
      <thead><tr><th>Servicio</th><th>Búsquedas</th><th>Reportes 24 h</th></tr></thead>
      <tbody>
      <?php foreach ($top as $r): ?>
        <tr><td><a href="/admin/servicios/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a><br><small><?= e($r['domain']) ?></small></td><td><?= (int) $r['search_count'] ?></td><td><?= (int) $r['r24'] ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
  <section class="card">
    <h2>Pendientes de revisión (no indexados)</h2>
    <p class="muted">Dominios que buscaron los visitantes. Revisá el contenido y activá la indexación solo si la ficha vale la pena.</p>
    <?php if (!$pending): ?><p class="empty">No hay pendientes.</p><?php else: ?>
    <ul class="plain">
      <?php foreach ($pending as $r): ?>
        <li><a href="/admin/servicios/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a> <small class="muted">(<?= e($r['domain']) ?> · <?= (int) $r['search_count'] ?> búsquedas)</small></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
