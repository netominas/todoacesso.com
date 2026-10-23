<?php
/** @var array $s */
?>
<a class="svc-card" href="<?= e(service_path($s)) ?>">
  <?= service_icon($s, 44) ?>
  <span class="svc-card__body">
    <span class="svc-card__name"><?= e($s['name']) ?></span>
    <span class="svc-card__domain"><?= e($s['domain']) ?></span>
  </span>
  <?= status_pill($s['last_status']) ?>
</a>
