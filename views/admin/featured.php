<?php use TodoAcesso\Core\Auth; /** @var array $rows @var ?array $flash */ ?>
<h1>Destacados de la home</h1>
<?php if ($flash): ?><p class="alert alert--<?= e($flash[0]) ?>"><?= e($flash[1]) ?></p><?php endif; ?>
<p class="muted">Los servicios se muestran en la home de menor a mayor número de orden. Para quitar o agregar destacados, editá el servicio.</p>
<form method="post" action="/admin/destacados" class="card">
  <?= Auth::csrfField() ?>
  <div class="table-wrap"><table class="admin-table">
    <thead><tr><th>Orden</th><th>Servicio</th><th>Indexable</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td style="width:110px"><input type="number" name="order[<?= (int) $r['id'] ?>]" value="<?= (int) $r['featured_order'] ?>" min="0" max="100000" aria-label="Orden de <?= e($r['name']) ?>" style="width:90px;padding:.4em;border:2px solid var(--line);border-radius:8px"></td>
        <td><a href="/admin/servicios/<?= (int) $r['id'] ?>"><?= service_icon($r, 28) ?> <?= e($r['name']) ?></a> <small class="muted"><?= e($r['domain']) ?></small></td>
        <td><?= $r['indexable'] ? 'Sí' : 'No' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <button class="btn btn--primary" type="submit">Guardar orden</button>
</form>
