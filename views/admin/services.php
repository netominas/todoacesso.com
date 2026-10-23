<?php /** @var array $rows @var int $total @var string $q @var string $filter @var int $page @var int $pages @var ?array $flash */
$qs = static fn (array $over = []): string => '?' . http_build_query(array_filter(array_merge(['q' => $q, 'f' => $filter, 'p' => $page], $over), static fn ($v) => $v !== '' && $v !== null && $v !== 1));
?>
<h1>Servicios <small class="muted">(<?= $total ?>)</small></h1>
<?php if ($flash): ?><p class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></p><?php endif; ?>
<form class="toolbar" method="get" action="/admin/servicios">
  <div class="field"><label for="q">Buscar</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="dominio o nombre"></div>
  <div class="field"><label for="f">Filtro</label>
    <select id="f" name="f">
      <option value="">Todos</option>
      <option value="featured"<?= $filter === 'featured' ? ' selected' : '' ?>>Destacados</option>
      <option value="indexable"<?= $filter === 'indexable' ? ' selected' : '' ?>>Indexables</option>
      <option value="pending"<?= $filter === 'pending' ? ' selected' : '' ?>>No indexados</option>
    </select></div>
  <button class="btn btn--primary" type="submit">Filtrar</button>
  <a class="btn btn--ghost" href="/admin/servicios/nuevo">+ Nuevo servicio</a>
</form>
<div class="table-wrap"><table class="admin-table">
  <thead><tr><th>Servicio</th><th>Estado</th><th>Dest.</th><th>Orden</th><th>Index.</th><th>Búsquedas</th><th>Reportes</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="/admin/servicios/<?= (int) $r['id'] ?>"><?= service_icon($r, 28) ?> <?= e($r['name']) ?></a><br><small class="muted"><?= e($r['domain']) ?></small></td>
      <td><?= status_pill($r['last_status']) ?></td>
      <td><?= $r['featured'] ? 'Sí' : '—' ?></td>
      <td><?= $r['featured'] ? (int) $r['featured_order'] : '—' ?></td>
      <td><?= $r['indexable'] ? 'Sí' : 'No' ?></td>
      <td><?= (int) $r['search_count'] ?></td>
      <td><?= (int) $r['report_count'] ?></td>
      <td><a class="btn btn--ghost btn--sm" href="<?= e(service_path($r)) ?>" target="_blank" rel="noopener">Ver página</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Paginación">
  <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="<?= e($qs(['p' => $page - 1])) ?>">← Anterior</a><?php endif; ?>
  <span>Página <?= $page ?> de <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn btn--ghost btn--sm" href="<?= e($qs(['p' => $page + 1])) ?>">Siguiente →</a><?php endif; ?>
</nav>
<?php endif; ?>
