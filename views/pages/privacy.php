<?php /** @var array $crumbs @var string $contactEmail @var int $updated */ ?>
<div class="wrap narrow">
  <?= \TodoAcceso\Core\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <article class="prose">
    <h1>Política de privacidad</h1>
    <p class="muted">Última actualización: <?= e((new DateTimeImmutable('@' . $updated))->setTimezone(new DateTimeZone(TZ_AR))->format('d/m/Y')) ?></p>
    <p class="lead">En Todo Acceso recolectamos la menor cantidad de datos posible. No hay registro de usuarios y no vendemos información personal. Acá te explicamos qué pasa con cada dato.</p>

    <h2 id="busquedas">Dominios que buscás</h2>
    <p>Cuando consultás un dominio, lo normalizamos (sin protocolo, sin "www" y sin rutas ni parámetros) y sumamos una consulta al contador del servicio. Ese contador es una métrica agregada: no la asociamos con tu identidad.</p>

    <h2 id="reportes">Reportes y hash anónimo</h2>
    <p>Cuando informás un problema guardamos: el servicio, el tipo de problema, la fecha y hora y, si lo autorizás, una ubicación aproximada. Para evitar duplicados y spam generamos un <strong>hash anónimo</strong> a partir de datos técnicos de tu conexión y navegador, firmado con una clave privada del servidor. Ese hash no permite recuperar tu dirección IP ni identificarte, y no guardamos tu dirección IP en la base de datos.</p>

    <h2 id="ubicacion">Ubicación (opcional)</h2>
    <ul>
      <li>La ubicación es <strong>totalmente voluntaria</strong>. No hay un botón aparte: el navegador te la solicita recién cuando enviás un reporte, y podés negarte sin ningún efecto sobre el reporte.</li>
      <li>Si la autorizás, redondeamos las coordenadas (aproximadamente 10 km) antes de guardarlas y verificamos que estén dentro de Argentina.</li>
      <li>En el mapa nunca mostramos puntos individuales: los reportes se agrupan por ciudad de referencia y se muestra el total de cada zona.</li>
      <li>No intentamos identificar a ninguna persona a partir de su ubicación.</li>
    </ul>

    <h2 id="mapa">Mapa y OpenStreetMap</h2>
    <p>El mapa usa Leaflet y teselas de OpenStreetMap. Al visualizar el mapa, tu navegador se conecta a los servidores de OpenStreetMap para descargar las imágenes del mapa, y estos pueden registrar datos técnicos como tu dirección IP según su propia política. El mapa se carga solo en las páginas de servicios, cuando llegás a esa sección.</p>

    <h2 id="logs">Registros técnicos</h2>
    <p>Nuestros servidores guardan registros técnicos (por ejemplo, fecha, ruta solicitada y errores) con fines de seguridad y funcionamiento. Los registros se conservan por períodos limitados y se eliminan o rotan periódicamente.</p>

    <h2 id="cookies">Cookies</h2>
    <p>El sitio público no usa cookies propias de seguimiento ni requiere cuenta. Existe una cookie de sesión técnica y estricta solo para el panel administrativo. Si activamos Google Tag Manager, ese servicio puede establecer cookies según las etiquetas que se configuren.</p>

    <h2 id="gtm">Google Tag Manager y servicios de terceros</h2>
    <p>Podemos utilizar Google Tag Manager para medir el uso del sitio de forma agregada (por ejemplo, con Google Analytics). Esos servicios son operados por terceros y se rigen por sus propias políticas de privacidad. Para verificar los sitios consultados, nuestro servidor se conecta a ellos; no les enviamos tus datos personales.</p>

    <h2 id="retencion">Cuánto tiempo conservamos los datos</h2>
    <p>Los reportes se conservan un máximo de 90 días y luego se eliminan automáticamente. Las métricas agregadas (como el número de consultas por servicio) no contienen datos personales y pueden conservarse por más tiempo.</p>

    <h2 id="derechos">Tus derechos</h2>
    <p>De acuerdo con la Ley 25.326 de Protección de los Datos Personales de la República Argentina, tenés derecho a acceder, rectificar y suprimir tus datos personales. Como los reportes son anónimos, en la práctica no podemos asociarlos con una persona, pero podés escribirnos a <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a> para cualquier consulta. La Agencia de Acceso a la Información Pública (AAIP), como órgano de control de la ley, tiene la atribución de atender denuncias y reclamos por incumplimiento de las normas de protección de datos personales.</p>

    <h2 id="cambios">Cambios en esta política</h2>
    <p>Si modificamos esta política, publicaremos la nueva versión en esta página con su fecha de actualización.</p>
  </article>
</div>
