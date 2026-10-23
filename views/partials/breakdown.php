<?php /** @var array $activity */ ?>
<?php if (!$activity['distribution']): ?>
  <p class="empty">Todavía no hay reportes en las últimas 24 horas. Si algo no funciona, sé el primero en avisar.</p>
<?php else: ?>
  <ul class="bars">
    <?php foreach ($activity['distribution'] as $d): ?>
      <li>
        <div class="bars__row"><span><?= e($d['label']) ?></span><strong><?= (int) $d['count'] ?> <small>(<?= (int) $d['pct'] ?>%)</small></strong></div>
        <div class="bars__track" aria-hidden="true"><div class="bars__fill" style="width:<?= max(2, (int) $d['pct']) ?>%"></div></div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
