<?php use TodoAcesso\Core\Auth; /** @var ?string $error @var string $value */ ?>
<h1>Nuevo servicio</h1>
<?php if ($error): ?><p class="alert alert--error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" action="/admin/servicios/nuevo" class="card form-grid" style="max-width:560px">
  <?= Auth::csrfField() ?>
  <div class="field"><label for="domain">Dominio o URL</label><input id="domain" name="domain" type="text" value="<?= e($value) ?>" required placeholder="mercadolibre.com.ar">
    <p class="hint">Se normaliza igual que en el buscador público. Se crea como no indexable; al crearlo se verifica e importa metadata e imágenes.</p></div>
  <button class="btn btn--primary" type="submit">Crear y verificar</button>
</form>
