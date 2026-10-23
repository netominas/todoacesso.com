<?php
/** @var array<int,array{0:string,1:?string}> $crumbs */
?>
<nav class="breadcrumbs" aria-label="Ruta de navegación">
  <ol>
    <?php foreach ($crumbs as $i => [$label, $href]): ?>
      <li><?php if ($href !== null): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ol>
</nav>
