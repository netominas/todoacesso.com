<?php
/** @var array $activity @var array $service */
$slots = $activity['slots'];
$n = count($slots);
$maxY = max(1, (int) ceil($activity['max']));
$W = 720; $H = 300; $pl = 38; $pr = 10; $pt = 14; $pb = 36;
$pw = $W - $pl - $pr; $ph = $H - $pt - $pb; $bw = $pw / $n;
$y = static fn (float $v): float => $pt + $ph - ($v / $maxY) * $ph;
$high = $activity['level'] === 'high';
$empty = $activity['total'] === 0;

$line = '';
foreach ($slots as $i => $s) {
    $line .= ($i === 0 ? 'M' : 'L') . round($pl + $i * $bw + $bw / 2, 1) . ',' . round($y($s['baseline']), 1) . ' ';
}
$ticks = array_values(array_unique([0, (int) round($maxY / 2), $maxY]));
?>
<p class="activity-state activity-state--<?= $high ? 'high' : 'normal' ?>">
  <strong><span aria-hidden="true"><?= $high ? '▲' : '●' ?></span> <?= $high ? 'Actividad elevada' : 'Actividad normal' ?></strong>
  <span><?= (int) $activity['total'] ?> <?= $activity['total'] === 1 ? 'reporte' : 'reportes' ?> en las últimas 24 horas</span>
</p>
<figure class="chart">
  <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-labelledby="chart-t chart-d" preserveAspectRatio="xMidYMid meet">
    <title id="chart-t">Reportes de <?= e($service['name']) ?> en las últimas 24 horas</title>
    <desc id="chart-d"><?= $empty ? 'Todavía no hay reportes en las últimas 24 horas.' : 'Barras con los reportes recibidos cada 30 minutos y línea punteada con la actividad habitual de los últimos 7 días.' ?></desc>
    <?php foreach ($ticks as $t): ?>
      <line class="chart__grid" x1="<?= $pl ?>" x2="<?= $W - $pr ?>" y1="<?= round($y($t), 1) ?>" y2="<?= round($y($t), 1) ?>"/>
      <text class="chart__axis" x="<?= $pl - 6 ?>" y="<?= round($y($t) + 4, 1) ?>" text-anchor="end"><?= $t ?></text>
    <?php endforeach; ?>
    <?php foreach ($slots as $i => $s):
        if ($s['count'] <= 0) { continue; }
        $h = ($s['count'] / $maxY) * $ph; ?>
      <rect class="chart__bar" x="<?= round($pl + $i * $bw + 1, 1) ?>" y="<?= round($pt + $ph - $h, 1) ?>" width="<?= round($bw - 2, 1) ?>" height="<?= round($h, 1) ?>" rx="2"><title><?= e(fmt_time($s['start'])) ?>: <?= (int) $s['count'] ?> <?= $s['count'] === 1 ? 'reporte' : 'reportes' ?></title></rect>
    <?php endforeach; ?>
    <path class="chart__base" d="<?= $line ?>" fill="none"/>
    <?php foreach ($slots as $i => $s):
        if ($i % 8 !== 0) { continue; } ?>
      <text class="chart__axis<?= $i === 40 ? ' chart__axis--late' : '' ?>"x="<?= round($pl + $i * $bw, 1) ?>" y="<?= $H - 14 ?>" text-anchor="start"><?= e(fmt_time($s['start'])) ?></text>
    <?php endforeach; ?>
    <text class="chart__axis" x="<?= $W - $pr ?>" y="<?= $H - 14 ?>" text-anchor="end">ahora</text>
    <?php if ($empty): ?>
      <text class="chart__empty" x="<?= round($pl + $pw / 2) ?>" y="<?= round($pt + $ph / 2) ?>" text-anchor="middle">Sin reportes en las últimas 24 horas</text>
    <?php endif; ?>
  </svg>
  <figcaption class="chart__legend">
    <span class="legend"><i class="legend__bar" aria-hidden="true"></i> Reportes recibidos (cada 30 min)</span>
    <span class="legend"><i class="legend__dash" aria-hidden="true"></i> Actividad habitual (promedio de 7 días)</span>
  </figcaption>
  <details class="chart__table">
    <summary>Ver los datos del gráfico en una tabla</summary>
    <?php $with = array_filter($slots, static fn ($s) => $s['count'] > 0); ?>
    <?php if (!$with): ?>
      <p>No hay reportes en este período.</p>
    <?php else: ?>
      <table>
        <thead><tr><th scope="col">Hora (Argentina)</th><th scope="col">Reportes</th><th scope="col">Habitual</th></tr></thead>
        <tbody>
        <?php foreach ($with as $s): ?>
          <tr><td><?= e(fmt_time($s['start'])) ?></td><td><?= (int) $s['count'] ?></td><td><?= e(number_format($s['baseline'], 1, ',', '')) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p class="note">Los intervalos que no aparecen no tuvieron reportes.</p>
    <?php endif; ?>
  </details>
</figure>
