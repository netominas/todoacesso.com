<?php
/** @var string $id @var bool $compact @var string $query */
?>
<form class="search<?= !empty($compact) ? ' search--compact' : '' ?>" action="/buscar" method="get" role="search" novalidate>
  <label class="<?= !empty($compact) ? 'sr-only' : 'search__label' ?>" for="<?= e($id) ?>">Dominio o dirección del sitio</label>
  <div class="search__row">
    <input class="search__input" id="<?= e($id) ?>" name="q" type="text" inputmode="url" autocomplete="off" autocapitalize="none" spellcheck="false"
           placeholder="Ej.: whatsapp.com o https://www.mercadolibre.com.ar" value="<?= e($query ?? '') ?>" required maxlength="2048">
    <button class="btn btn--primary search__btn" type="submit">Comprobar estado</button>
  </div>
  <?php if (empty($compact)): ?>
  <p class="search__hint">Podés escribir el dominio (<code>instagram.com</code>) o pegar la dirección completa. Nosotros nos encargamos del resto.</p>
  <?php endif; ?>
</form>
