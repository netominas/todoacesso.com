<?php /** @var array $featured @var ?string $error @var string $query */ ?>
<section class="hero">
  <div class="wrap hero__inner">
    <p class="eyebrow eyebrow--light">Estado de sitios y apps en Argentina</p>
    <h1 class="hero__title">¿Un sitio o una app no funciona? <span>Comprobá su estado en segundos.</span></h1>
    <p class="hero__lead">Escribí el dominio o pegá la dirección y te mostramos si responde, qué tan rápido lo hace y qué reportan otras personas en las últimas 24 horas.</p>
    <?php if ($error): ?>
      <p class="alert alert--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <div class="hero__search">
      <?= \TodoAcceso\Core\View::partial('partials/search_form', ['id' => 'q-home', 'compact' => false, 'query' => $query]) ?>
    </div>
    <ul class="hero__chips" aria-label="Consultas frecuentes">
      <?php foreach (array_slice($featured, 0, 6) as $s): ?>
        <li><a href="<?= e(service_path($s)) ?>">¿<?= e($s['name']) ?> funciona?</a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section wrap" aria-labelledby="destacados-title">
  <div class="section__head">
    <h2 id="destacados-title">Servicios más consultados en Argentina</h2>
    <a class="link-more" href="/servicios-populares">Ver ranking completo →</a>
  </div>
  <div class="svc-grid">
    <?php foreach ($featured as $s): ?>
      <?= \TodoAcceso\Core\View::partial('partials/service_card', ['s' => $s]) ?>
    <?php endforeach; ?>
  </div>
  <p class="note">El estado de cada servicio corresponde a la última verificación automática. Puede variar según la ciudad, la operadora o el dispositivo.</p>
</section>

<section class="section section--tint" id="como-funciona" aria-labelledby="como-title">
  <div class="wrap">
    <h2 id="como-title">Cómo funciona Todo Acceso</h2>
    <ol class="steps">
      <li class="step">
        <span class="step__n" aria-hidden="true">1</span>
        <h3>Buscá el servicio</h3>
        <p>Ingresá un dominio como <code>mercadopago.com.ar</code> o pegá una dirección completa. Normalizamos todo para que no haya duplicados.</p>
      </li>
      <li class="step">
        <span class="step__n" aria-hidden="true">2</span>
        <h3>Verificamos la respuesta</h3>
        <p>Consultamos el sitio de forma segura y registramos el código HTTP, el tiempo de respuesta y la hora de la verificación.</p>
      </li>
      <li class="step">
        <span class="step__n" aria-hidden="true">3</span>
        <h3>Sumá tu reporte</h3>
        <p>Si algo falla, contanos qué pasa. Los reportes son anónimos y ayudan a otros a saber si el problema es general o local.</p>
      </li>
    </ol>
  </div>
</section>

<section class="section wrap" aria-labelledby="beneficios-title">
  <h2 id="beneficios-title">Por qué usar Todo Acceso</h2>
  <div class="benefits">
    <article class="benefit"><h3>Salí de la duda rápido</h3><p>Sabé en segundos si el problema es del servicio o de tu conexión, sin probar mil cosas.</p></article>
    <article class="benefit"><h3>Hecho para Argentina</h3><p>Reportes y mapa pensados para el país, con servicios que usás todos los días: bancos, billeteras, operadoras y más.</p></article>
    <article class="benefit"><h3>Privacidad primero</h3><p>No pedimos registro. La ubicación es opcional, se redondea y solo se muestra agrupada por ciudad.</p></article>
    <article class="benefit"><h3>Información clara</h3><p>Distinguimos "el servidor respondió" de "todo funciona perfecto", sin afirmar caídas que no podemos comprobar.</p></article>
  </div>
</section>

<section class="section section--tint" aria-labelledby="seo-title">
  <div class="wrap prose">
    <h2 id="seo-title">Comprobá si un sitio o una app está caído</h2>
    <p>Cuando WhatsApp no envía mensajes, Instagram no carga o la billetera virtual no deja transferir, lo primero que queremos saber es si le pasa a todos o solo a nosotros. <strong>Todo Acceso</strong> te ayuda a responderlo: verificamos si el servidor del servicio responde desde nuestra infraestructura y mostramos los reportes que dejan otros usuarios en Argentina.</p>
    <p>Podés consultar servicios populares como <a href="/estado/whatsapp-com">WhatsApp</a>, <a href="/estado/instagram-com">Instagram</a>, <a href="/estado/chatgpt-com">ChatGPT</a>, <a href="/estado/mercadolibre-com-ar">Mercado Libre</a> y <a href="/estado/mercadopago-com-ar">Mercado Pago</a>, o escribir el dominio de cualquier sitio público. Cada ficha incluye el gráfico de reportes de las últimas 24 horas, el mapa aproximado de la actividad y una guía para distinguir una falla local de una caída general.</p>
    <p>Una verificación puntual no es una garantía: un servicio puede responder en nuestro servidor y fallar en tu ciudad, con tu operadora o en una función específica. Por eso combinamos la verificación técnica con los reportes de la comunidad.</p>
  </div>
</section>
