<?php
use TodoAcesso\Core\Auth;
use TodoAcesso\Services\Status;
/** @var array $service @var int $reports @var ?string $iconUrl @var ?string $heroUrl @var array $categories @var ?array $flash */
$id = (int) $service['id'];
?>
<h1><?= service_icon($service, 40) ?> <?= e($service['name']) ?> <small class="muted"><?= e($service['domain']) ?></small></h1>
<?php if ($flash): ?><p class="alert alert--<?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></p><?php endif; ?>

<div class="stat-grid">
  <div class="stat"><b><?= (int) $service['search_count'] ?></b>Búsquedas</div>
  <div class="stat"><b><?= $reports ?></b>Reportes</div>
  <div class="stat"><b><?= e(Status::label($service['last_status'])) ?></b>Último estado</div>
  <div class="stat"><b><?= $service['last_http_code'] ? (int) $service['last_http_code'] : '—' ?></b>HTTP · <?= $service['last_response_ms'] !== null ? (int) $service['last_response_ms'] . ' ms' : '—' ?></div>
  <div class="stat"><b style="font-size:1rem"><?= e(fmt_datetime($service['metadata_checked_at'] ? (int) $service['metadata_checked_at'] : null)) ?></b>Última importación de metadatos</div>
</div>

<div class="card" style="margin-bottom:16px">
  <p><a class="btn btn--ghost btn--sm" href="<?= e(service_path($service)) ?>" target="_blank" rel="noopener">Ver página pública ↗</a></p>
  <form method="post" action="/admin/servicios/<?= $id ?>/accion" class="toolbar">
    <?= Auth::csrfField() ?>
    <button class="btn btn--ghost btn--sm" name="do" value="recheck" type="submit">Verificar ahora</button>
    <button class="btn btn--ghost btn--sm" name="do" value="import" type="submit">Forzar importación de metadatos e imágenes</button>
    <label class="check"><input type="checkbox" name="overwrite" value="1"> Reemplazar también descripción e imágenes manuales</label>
  </form>
</div>

<form method="post" action="/admin/servicios/<?= $id ?>" enctype="multipart/form-data" class="card form-grid">
  <?= Auth::csrfField() ?>
  <div class="field"><label for="name">Nombre</label><input id="name" name="name" type="text" value="<?= e($service['name']) ?>" required maxlength="120"></div>
  <div class="field"><label for="category">Categoría</label>
    <select id="category" name="category">
      <?php foreach ($categories as $k => $label): ?><option value="<?= e($k) ?>"<?= $service['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select>
    <p class="hint">Define la plantilla de contenido editorial de la página.</p></div>
  <div class="field"><label for="description">Descripción (<?= e($service['description_source']) ?>)</label>
    <textarea id="description" name="description" maxlength="600"><?= e($service['description']) ?></textarea>
    <p class="hint">Si la editás, queda como "manual" y la importación automática no la pisa.</p></div>
  <div class="field"><label for="editorial">Contenido editorial propio (opcional)</label>
    <textarea id="editorial" name="editorial" style="min-height:200px"><?= e($service['editorial']) ?></textarea>
    <p class="hint">Texto plano; separá los párrafos con una línea en blanco. Reemplaza la sección "¿Qué es …?". Las demás secciones se generan automáticamente.</p></div>
  <div class="field" style="display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    <label class="check"><input type="checkbox" name="featured" value="1"<?= $service['featured'] ? ' checked' : '' ?>> Destacado en la home</label>
    <label class="check"><input type="checkbox" name="indexable" value="1"<?= $service['indexable'] ? ' checked' : '' ?>> Indexable (sitemap + index,follow)</label>
    <div><label for="featured_order">Orden en la home</label><input id="featured_order" name="featured_order" type="number" min="0" max="100000" value="<?= (int) $service['featured_order'] ?>"></div>
  </div>
  <div class="field">
    <span class="label">Ícono actual</span>
    <?php if ($iconUrl): ?><img class="img-preview" src="<?= e($iconUrl) ?>" alt="Ícono actual"><small class="muted">Origen: <?= e($service['icon_source_url'] === 'manual' ? 'subida manual' : (string) $service['icon_source_url']) ?></small><br><label class="check"><input type="checkbox" name="remove_icon" value="1"> Quitar ícono</label><?php else: ?><p class="muted">Sin ícono (se muestra la inicial).</p><?php endif; ?>
    <label for="icon_file">Reemplazar ícono</label><input id="icon_file" name="icon_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif,image/x-icon">
  </div>
  <div class="field">
    <span class="label">Imagen de destacado actual</span>
    <?php if ($heroUrl): ?><img class="img-preview" src="<?= e($heroUrl) ?>" alt="Imagen de destacado actual" style="max-width:260px"><small class="muted">Origen: <?= e($service['hero_image_source_url'] === 'manual' ? 'subida manual' : (string) $service['hero_image_source_url']) ?></small><br><label class="check"><input type="checkbox" name="remove_hero" value="1"> Quitar imagen</label><?php else: ?><p class="muted">Sin imagen de destacado (se usa la imagen por defecto del sitio para compartir).</p><?php endif; ?>
    <label for="hero_file">Reemplazar imagen</label><input id="hero_file" name="hero_file" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
    <p class="hint">PNG, JPG, WebP o GIF (no SVG). Se re-codifica en el servidor y se limita a 1200 px de ancho.</p>
  </div>
  <div><button class="btn btn--primary" type="submit">Guardar cambios</button></div>
</form>

<div class="card" style="margin-top:16px">
  <h2>Zona de riesgo</h2>
  <form method="post" action="/admin/servicios/<?= $id ?>/accion" class="toolbar" onsubmit="return confirm('¿Seguro?');">
    <?= Auth::csrfField() ?>
    <button class="btn btn--danger btn--sm" name="do" value="clear_reports" type="submit">Borrar reportes de este servicio</button>
    <button class="btn btn--danger btn--sm" name="do" value="delete" type="submit">Eliminar servicio</button>
  </form>
</div>
