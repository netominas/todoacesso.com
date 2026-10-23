<?php
declare(strict_types=1);

namespace TodoAcesso\Content;

/**
 * Contenido editorial base por categoría. {n} = nombre del servicio, {d} = dominio.
 * Los servicios prioritarios lo complementan o reemplazan en ServiceContent::SPECIFIC.
 */
final class Categories
{
    public const LABELS = [
        'sitio' => 'Sitio web',
        'mensajeria' => 'Mensajería',
        'red_social' => 'Redes sociales',
        'ia' => 'Inteligencia artificial',
        'buscador' => 'Buscadores y correo',
        'video' => 'Video y música',
        'ecommerce' => 'Compras online',
        'billetera' => 'Billeteras y pagos',
        'banco' => 'Bancos',
        'operadora' => 'Internet y telefonía',
        'gobierno' => 'Organismos públicos',
        'transporte' => 'Transporte y correo',
    ];

    /** @return array<string,mixed> */
    public static function get(string $category): array
    {
        $all = self::data();
        return $all[$category] ?? $all['sitio'];
    }

    /** @return array<string,array<string,mixed>> */
    private static function data(): array
    {
        return [
            'sitio' => [
                'kind' => 'sitio web',
                'about' => [
                    '{n} es un sitio web que se encuentra en {d}. En Todo Acesso comprobamos si el servidor responde y juntamos los reportes de otras personas para que puedas saber, en pocos segundos, si lo que te pasa le está pasando también a otros usuarios.',
                    'Esta página no pertenece a {n} ni está vinculada con la empresa: es una herramienta independiente de consulta.',
                ],
                'uses' => ['Acceder a los contenidos y funciones que ofrece {d}.', 'Consultar información, iniciar sesión o realizar trámites, según el tipo de sitio.'],
                'platforms' => ['Navegadores de escritorio (Chrome, Edge, Firefox, Safari).', 'Navegadores en celulares y tablets.'],
                'problems' => [
                    ['El sitio no carga', 'La página queda en blanco, tarda demasiado o muestra un mensaje de error de conexión.'],
                    ['Errores del servidor', 'Aparecen mensajes como 500, 502, 503 o 504, que indican problemas del lado del servicio.'],
                    ['Problemas al iniciar sesión', 'El sitio abre, pero no reconoce tus datos o no termina de cargar tu cuenta.'],
                ],
                'faq' => [],
            ],
            'mensajeria' => [
                'kind' => 'servicio de mensajería',
                'about' => [
                    '{n} es un servicio de mensajería que se usa para enviar mensajes, fotos, audios y archivos, y para hacer llamadas desde el celular o la computadora.',
                    'Cuando se cae, el efecto se nota enseguida: los mensajes se quedan con el reloj, no llegan las notificaciones o la aplicación no logra conectarse. Por eso conviene saber rápido si el problema es general o de tu conexión.',
                ],
                'uses' => ['Chatear y mandar audios, fotos, videos y documentos.', 'Hacer llamadas y videollamadas por internet.', 'Armar grupos de trabajo, familia o amigos.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'Versión web y aplicación de escritorio, según el servicio.'],
                'problems' => [
                    ['Mensajes que no se envían', 'Los mensajes quedan con el reloj o con una sola tilde y no se entregan.'],
                    ['No llegan las notificaciones', 'Los mensajes aparecen recién al abrir la aplicación.'],
                    ['Llamadas con cortes', 'Las llamadas y videollamadas se entrecortan o no conectan.'],
                    ['Fallas de conexión del lado del servicio', 'La aplicación muestra "Conectando…" de forma prolongada aunque otras apps funcionen.'],
                ],
                'faq' => [],
            ],
            'red_social' => [
                'kind' => 'red social',
                'about' => [
                    '{n} es una red social donde las personas comparten publicaciones, fotos, videos y mensajes, y siguen a amigos, creadores y marcas.',
                    'Como millones de personas la usan al mismo tiempo, cualquier falla se percibe rápido: el feed no actualiza, no se pueden subir contenidos o la aplicación se cierra sola.',
                ],
                'uses' => ['Ver y publicar fotos, videos e historias.', 'Mantener el contacto con amigos y familiares.', 'Seguir noticias, marcas y creadores.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'Sitio web para computadora y celular.'],
                'problems' => [
                    ['El feed no carga', 'La pantalla principal queda en blanco o muestra el mensaje de "algo salió mal".'],
                    ['No se pueden subir publicaciones', 'Las fotos y los videos se quedan cargando o fallan al publicar.'],
                    ['Problemas de acceso', 'La aplicación pide iniciar sesión de nuevo o no reconoce la cuenta.'],
                    ['Mensajes directos con demoras', 'Los chats tardan en enviarse o en mostrar los mensajes nuevos.'],
                ],
                'faq' => [],
            ],
            'ia' => [
                'kind' => 'servicio de inteligencia artificial',
                'about' => [
                    '{n} es un servicio de inteligencia artificial al que se accede desde la web o desde una aplicación para conversar, escribir, programar, traducir o resumir textos.',
                    'Como los modelos de inteligencia artificial consumen muchos recursos, estos servicios suelen mostrar mensajes de saturación o respuestas lentas cuando hay mucha demanda.',
                ],
                'uses' => ['Redactar y corregir textos.', 'Resumir documentos y traducir.', 'Ayudar con programación, estudio y trabajo.'],
                'platforms' => ['Sitio web.', 'Aplicaciones para Android y iPhone.'],
                'problems' => [
                    ['Respuestas que se cortan o no llegan', 'La respuesta se detiene a la mitad o aparece un mensaje de error.'],
                    ['Mensajes de saturación', 'El servicio avisa que tiene demasiada demanda y pide intentar más tarde.'],
                    ['No se puede iniciar sesión', 'El acceso falla o la sesión se cierra continuamente.'],
                    ['Lentitud', 'Las respuestas tardan mucho más que lo habitual.'],
                ],
                'faq' => [],
            ],
            'buscador' => [
                'kind' => 'servicio de búsqueda y correo',
                'about' => [
                    '{n} forma parte de los servicios que usamos todos los días para buscar información, enviar correos o acceder a otras herramientas con una misma cuenta.',
                    'Una falla en este tipo de servicios puede parecer más grande de lo que es, porque muchas otras aplicaciones dependen de la misma cuenta para iniciar sesión.',
                ],
                'uses' => ['Buscar información en internet.', 'Enviar y recibir correos electrónicos.', 'Acceder a otras herramientas con la misma cuenta.'],
                'platforms' => ['Navegadores de escritorio y celular.', 'Aplicaciones para Android y iPhone.'],
                'problems' => [
                    ['No cargan los resultados o el correo', 'La página queda en blanco o muestra un error 5xx.'],
                    ['No se envían ni reciben correos', 'Los mensajes quedan en la bandeja de salida o demoran en llegar.'],
                    ['Problemas con la cuenta', 'Pide verificar la identidad o no deja iniciar sesión.'],
                ],
                'faq' => [],
            ],
            'video' => [
                'kind' => 'plataforma de video y música',
                'about' => [
                    '{n} es una plataforma de entretenimiento donde se miran videos, series o películas, o se escucha música, desde el celular, la computadora o el televisor.',
                    'Al depender tanto de la velocidad de internet, muchas veces lo que parece una caída del servicio es en realidad un problema de conexión local, de la aplicación o del televisor.',
                ],
                'uses' => ['Mirar videos, series y películas.', 'Escuchar música y podcasts.', 'Guardar contenidos para ver sin conexión.'],
                'platforms' => ['Smart TV y dispositivos de streaming.', 'Aplicaciones para Android y iPhone.', 'Navegadores web.'],
                'problems' => [
                    ['El video no carga o se corta', 'La reproducción se detiene o queda cargando de forma permanente.'],
                    ['Error al abrir la aplicación', 'La app se cierra o muestra un código de error.'],
                    ['Baja calidad de imagen', 'La resolución baja de golpe aunque tengas buena conexión.'],
                    ['No se puede iniciar sesión', 'La cuenta no ingresa en el televisor o en la aplicación.'],
                ],
                'faq' => [],
            ],
            'ecommerce' => [
                'kind' => 'plataforma de compras online',
                'about' => [
                    '{n} es una plataforma de compras online donde se publican y compran productos, con envíos y medios de pago integrados.',
                    'Una falla en una plataforma de compras puede afectar la búsqueda de productos, el carrito, el pago o el seguimiento del envío; por eso es útil distinguir en qué parte exacta del proceso falla.',
                ],
                'uses' => ['Buscar y comprar productos.', 'Vender artículos y gestionar publicaciones.', 'Seguir envíos y pagos.'],
                'platforms' => ['Sitio web.', 'Aplicaciones para Android y iPhone.'],
                'problems' => [
                    ['La página no carga o va lenta', 'Los resultados de búsqueda o las publicaciones tardan en aparecer.'],
                    ['Fallas al pagar', 'El pago se rechaza, se queda procesando o la pantalla de pago no abre.'],
                    ['Problemas con la cuenta', 'No se puede iniciar sesión o falta el código de verificación.'],
                    ['Errores en publicaciones y mensajes', 'No se pueden responder preguntas ni actualizar publicaciones.'],
                ],
                'faq' => [],
            ],
            'billetera' => [
                'kind' => 'billetera virtual y servicio de pagos',
                'about' => [
                    '{n} es un servicio financiero digital que permite guardar dinero, transferir, pagar con QR y operar desde el celular.',
                    'Por tratarse de dinero, una falla genera mucha preocupación. Antes de asumir lo peor, conviene comprobar si el servicio está caído para todos o si es un problema puntual de tu cuenta, tu conexión o la aplicación.',
                ],
                'uses' => ['Transferir dinero y pagar con QR.', 'Pagar servicios e impuestos.', 'Consultar saldo y movimientos.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'Sitio web.'],
                'problems' => [
                    ['La aplicación no abre o no carga el saldo', 'Queda en pantalla de carga o muestra un error temporal.'],
                    ['Transferencias y pagos demorados', 'La operación queda pendiente o no se confirma.'],
                    ['Pagos con QR que no se procesan', 'El comercio no recibe la confirmación o la app no lee el código.'],
                    ['Problemas de acceso', 'No llega el código de verificación o falla la validación de identidad.'],
                ],
                'faq' => [],
            ],
            'banco' => [
                'kind' => 'banco',
                'about' => [
                    '{n} es una entidad financiera que ofrece sus servicios por home banking, aplicación móvil y sucursales. Las plataformas digitales concentran consultas de saldo, transferencias, pagos y trámites.',
                    'Los bancos suelen tener ventanas de mantenimiento programado, especialmente de madrugada o los fines de semana. Si no podés ingresar, revisá primero si hay un aviso oficial antes de pensar en una falla general.',
                ],
                'uses' => ['Consultar saldos y movimientos.', 'Hacer transferencias y pagar servicios.', 'Gestionar tarjetas, préstamos e inversiones.'],
                'platforms' => ['Home banking web.', 'Aplicación móvil para Android y iPhone.'],
                'problems' => [
                    ['No se puede ingresar al home banking', 'La página no carga o rechaza los datos de acceso aunque sean correctos.'],
                    ['La aplicación se cierra o queda cargando', 'Suele pasar tras una actualización o durante picos de uso.'],
                    ['Transferencias que no se acreditan', 'La operación figura como pendiente o falla la confirmación.'],
                    ['Problemas con tarjetas y cajeros', 'Las operaciones con tarjeta son rechazadas o hay demoras en las compras.'],
                ],
                'faq' => [
                    ['¿Qué hago si no puedo ingresar al home banking?', 'Verificá que tu usuario y clave sean correctos, probá desde otro dispositivo y revisá si el banco informó mantenimiento. Si persiste, comunicate con la entidad por sus canales oficiales. Nunca compartas tus claves con terceros.'],
                ],
            ],
            'operadora' => [
                'kind' => 'operadora de internet y telefonía',
                'about' => [
                    '{n} es una empresa de telecomunicaciones que brinda telefonía móvil, internet hogar y televisión, según el plan contratado.',
                    'Cuando una operadora tiene problemas, el efecto es distinto al de una aplicación: puede afectar a una zona o a un barrio sin que el sitio web de la empresa se vea afectado. Por eso, además del estado del sitio, importan los reportes por zona.',
                ],
                'uses' => ['Gestionar la cuenta, las facturas y los pagos.', 'Consultar el consumo de datos y cambiar de plan.', 'Pedir soporte técnico.'],
                'platforms' => ['Sitio web de autogestión.', 'Aplicación móvil para Android y iPhone.'],
                'problems' => [
                    ['Sin servicio de datos o señal', 'El celular muestra señal pero no navega, o no hay cobertura en la zona.'],
                    ['Internet hogar con cortes', 'El módem pierde la conexión o la velocidad cae de manera notoria.'],
                    ['La aplicación de autogestión no abre', 'No se puede consultar el consumo ni pagar la factura.'],
                    ['Problemas con la TV', 'La señal se corta o no cargan los canales.'],
                ],
                'faq' => [
                    ['¿Cómo sé si mi internet está caído o es una falla de la operadora?', 'Si todos los dispositivos de tu casa pierden conexión, reiniciá el módem. Si no vuelve, es probable que haya una falla de la operadora en tu zona. Revisá los reportes y comunicate con su servicio técnico.'],
                ],
            ],
            'gobierno' => [
                'kind' => 'organismo público',
                'about' => [
                    '{n} es un organismo o servicio público que ofrece trámites y consultas por internet. Durante vencimientos o períodos de mucha demanda, los sitios oficiales pueden responder lento o dejar de funcionar por momentos.',
                    'Todo Acesso es un sitio independiente y no tiene ninguna relación con el Estado. Para realizar trámites, usá siempre las direcciones y canales oficiales.',
                ],
                'uses' => ['Realizar trámites y consultas en línea.', 'Descargar constancias y comprobantes.', 'Acceder con clave o aplicación oficial.'],
                'platforms' => ['Sitio web oficial.', 'Aplicaciones oficiales para Android y iPhone, cuando existen.'],
                'problems' => [
                    ['El sitio responde muy lento', 'Sobre todo en fechas de vencimiento o de mucha demanda.'],
                    ['Mantenimiento programado', 'Algunos servicios se interrumpen por tareas técnicas, con o sin aviso previo.'],
                    ['Errores al iniciar sesión', 'La clave es correcta pero el acceso falla o expira durante el trámite.'],
                ],
                'faq' => [
                    ['¿Por qué un sitio del Estado puede estar lento aunque no esté caído?', 'En fechas de vencimiento o lanzamientos de trámites hay picos de demanda. El servidor sigue funcionando, pero responde más despacio. Probá en horarios de menor tráfico, como a la mañana temprano.'],
                ],
            ],
            'transporte' => [
                'kind' => 'servicio de transporte y logística',
                'about' => [
                    '{n} es un servicio vinculado al transporte, los viajes o los envíos, con herramientas online para gestionar reservas, seguimientos y trámites.',
                    'Si no podés usar el sitio o la aplicación, no necesariamente hay una caída general: a veces falla solo una función, como el pago, el seguimiento o la emisión de comprobantes.',
                ],
                'uses' => ['Consultar y gestionar reservas o envíos.', 'Seguir el estado de un viaje o paquete.', 'Pagar, recargar o descargar comprobantes.'],
                'platforms' => ['Sitio web.', 'Aplicación móvil para Android y iPhone.'],
                'problems' => [
                    ['El seguimiento no se actualiza', 'El estado del envío o del viaje no cambia o la búsqueda no devuelve resultados.'],
                    ['Errores en reservas y pagos', 'La operación se interrumpe antes de la confirmación.'],
                    ['El sitio no carga', 'Se muestra una pantalla en blanco o un mensaje de servicio no disponible.'],
                ],
                'faq' => [],
            ],
        ];
    }
}
