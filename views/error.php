<?php /** @var int $code @var string $title @var string $message */ ?>
<section class="wrap narrow error-page">
  <p class="eyebrow">Error <?= (int) $code ?></p>
  <h1><?= e($title) ?></h1>
  <p class="lead"><?= e($message) ?></p>
  <p><a class="btn btn--primary" href="/">Volver al inicio</a> <a class="btn btn--ghost" href="/servicios-populares">Ver servicios populares</a></p>
</section>
