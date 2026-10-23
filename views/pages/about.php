<?php /** @var array $crumbs @var string $contactEmail */ ?>
<div class="wrap narrow">
  <?= \TodoAcesso\Core\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <article class="prose">
    <h1>Sobre Todo Acesso</h1>
    <p class="lead">Todo Acesso es una herramienta gratuita e independiente para comprobar, en segundos, si un sitio web o una aplicación funciona en Argentina.</p>

    <h2>Qué hacemos</h2>
    <p>Cuando algo no carga, la pregunta es siempre la misma: ¿es un problema del servicio o mío? Para ayudarte a responderla, verificamos si el servidor del sitio consultado responde, medimos cuánto tarda y mostramos los reportes que dejan otras personas en las últimas 24 horas, junto con un gráfico y un mapa aproximado de la actividad.</p>

    <h2>Cómo verificamos un servicio</h2>
    <ul>
      <li>Resolvemos el dominio y nos conectamos de forma segura, primero por HTTPS y, si es necesario, por HTTP.</li>
      <li>Registramos el código de respuesta, el tiempo de respuesta y la hora de la verificación.</li>
      <li>Guardamos el resultado unos minutos para no sobrecargar a los sitios consultados.</li>
    </ul>
    <p>Una verificación puntual desde nuestra infraestructura no representa la experiencia de todos los usuarios. Por eso distinguimos entre <em>"el servidor respondió"</em> y <em>"el servicio funciona perfectamente"</em>, y complementamos el dato técnico con reportes de la comunidad.</p>

    <h2>Los reportes</h2>
    <p>Los reportes los envían personas de forma anónima y no se verifican. Son un indicio, no una confirmación oficial. Aplicamos límites y controles para reducir el spam, y nunca mostramos información que permita identificar a quien reporta.</p>

    <h2>Independencia</h2>
    <p>Todo Acesso no está afiliado, asociado ni patrocinado por ninguna de las marcas, empresas u organismos que aparecen en el sitio. Los nombres, logotipos e imágenes pertenecen a sus respectivos titulares y se usan solo para identificar el servicio consultado. Para recibir soporte o información oficial, recomendamos siempre acudir a los canales del propio servicio.</p>

    <h2>Contacto</h2>
    <p>Si querés sugerir un servicio, corregir información o pedir la baja de una ficha, escribinos a <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.</p>
  </article>
</div>
