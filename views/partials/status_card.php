<?php
/** @var array $service @var array $activity @var bool $stale */
use TodoAcesso\Services\Status;

$status = $service['last_status'];
$tone = Status::tone($status);
$high = $activity['level'] === 'high';
$reports24 = $activity['total'];

if ($status === Status::DOWN && $high) {
    $reading = 'Nuestro servidor no logró conectarse y además hay más reportes de lo habitual. Es probable que exista una falla.';
} elseif ($status === Status::DOWN) {
    $reading = 'No pudimos conectarnos desde nuestro servidor (' . Status::errorText($service['last_error']) . '). Puede tratarse de una caída o de una restricción hacia nuestro servidor: mirá los reportes de la comunidad.';
} elseif ($high) {
    $reading = 'El servidor responde, pero hay más reportes de lo habitual. Puede haber una falla parcial en alguna función.';
} elseif ($status === null || $status === Status::UNKNOWN) {
    $reading = 'Todavía no verificamos este servicio.';
} elseif (in_array($status, [Status::OK], true)) {
    $reading = 'No detectamos problemas: el servidor respondió con normalidad y no hay un aumento de reportes.';
} else {
    $reading = Status::summary($status) . '.';
}
?>
<div class="status-card status-card--<?= $tone ?>">
  <div class="status-card__main">
    <p class="status-card__label">Estado actual</p>
    <p class="status-card__state"><span aria-hidden="true"><?= Status::icon($status) ?></span> <?= e(Status::label($status)) ?></p>
    <p class="status-card__reading"><?= e($reading) ?></p>
    <?php if ($high): ?>
      <p class="activity-flag"><span aria-hidden="true">▲</span> Actividad elevada: <?= (int) $activity['recent'] ?> reportes en las últimas 2 horas (lo habitual sería ~<?= rtrim(rtrim(number_format($activity['baseline_recent'], 1, ',', ''), '0'), ',') ?: '0' ?>).</p>
    <?php endif; ?>
  </div>
  <dl class="metrics">
    <div><dt>Código HTTP</dt><dd><?= $service['last_http_code'] ? (int) $service['last_http_code'] : '—' ?></dd></div>
    <div><dt>Tiempo de respuesta</dt><dd><?= $service['last_response_ms'] !== null && $status !== Status::DOWN ? (int) $service['last_response_ms'] . ' ms' : '—' ?></dd></div>
    <div><dt>Última verificación</dt><dd><span title="<?= e(fmt_datetime($service['last_checked_at'] ? (int) $service['last_checked_at'] : null)) ?>"><?= e(time_ago($service['last_checked_at'] ? (int) $service['last_checked_at'] : null)) ?></span></dd></div>
    <div><dt>Reportes (24 h)</dt><dd><?= (int) $reports24 ?></dd></div>
  </dl>
</div>
