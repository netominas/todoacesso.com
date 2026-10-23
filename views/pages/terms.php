<?php /** @var array $crumbs @var string $contactEmail @var int $updated */ ?>
<div class="wrap narrow">
  <?= \TodoAcceso\Core\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <article class="prose">
    <h1>Términos y condiciones</h1>
    <p class="muted">Última actualización: <?= e((new DateTimeImmutable('@' . $updated))->setTimezone(new DateTimeZone(TZ_AR))->format('d/m/Y')) ?></p>
    <p class="lead">Al usar Todo Acceso aceptás estos términos. Si no estás de acuerdo, te pedimos que no utilices el sitio.</p>

    <h2>1. Naturaleza del servicio</h2>
    <p>Todo Acceso ofrece información <strong>meramente informativa y orientativa</strong> sobre el estado de sitios y aplicaciones. No es un servicio de monitoreo contratado ni garantiza la exactitud, integridad o disponibilidad de la información mostrada.</p>

    <h2>2. Verificaciones puntuales</h2>
    <p>Cada resultado corresponde a una verificación realizada en un momento determinado desde nuestra infraestructura. Que un servidor responda no implica que el servicio funcione correctamente para todas las personas, y un resultado negativo no implica una caída general.</p>

    <h2>3. Diferencias regionales</h2>
    <p>La disponibilidad de un servicio puede variar según la ciudad, la operadora, la red o el dispositivo desde donde se acceda. Los resultados de Todo Acceso pueden no coincidir con lo que experimentás vos.</p>

    <h2>4. Reportes de usuarios</h2>
    <p>Los reportes los envían personas anónimas y no son verificados. Todo Acceso no es responsable por su contenido ni por las conclusiones que se saquen de ellos.</p>

    <h2>5. Ausencia de vínculo con las marcas</h2>
    <p>Todo Acceso es independiente. No está afiliado, asociado, autorizado ni patrocinado por ninguna de las marcas, empresas u organismos mencionados. Los nombres, marcas, logotipos e imágenes pertenecen a sus respectivos titulares y se utilizan únicamente para identificar el servicio consultado.</p>

    <h2>6. Uso responsable y prohibición de abuso</h2>
    <ul>
      <li>Está prohibido enviar reportes falsos, repetitivos o automatizados, o intentar manipular las estadísticas.</li>
      <li>Está prohibido usar el sitio para sobrecargar a terceros, realizar ataques o explorar redes internas.</li>
      <li>Podemos limitar o bloquear el acceso a quien incumpla estas reglas.</li>
    </ul>

    <h2>7. Limitación de responsabilidad</h2>
    <p>En la máxima medida permitida por la ley, Todo Acceso no responde por daños derivados del uso de la información del sitio o de decisiones tomadas en base a ella.</p>

    <h2>8. Cambios y baja de fichas</h2>
    <p>Podemos modificar estos términos o el contenido del sitio en cualquier momento. Si sos titular de un servicio y querés corregir o dar de baja una ficha, escribinos a <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.</p>

    <h2>9. Ley aplicable</h2>
    <p>Estos términos se rigen por las leyes de la República Argentina.</p>
  </article>
</div>
