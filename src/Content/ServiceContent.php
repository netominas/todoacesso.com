<?php
declare(strict_types=1);

namespace TodoAcesso\Content;

/**
 * Arma el contenido editorial de la página de un servicio: plantilla por categoría + textos específicos
 * de los servicios prioritarios + secciones compartidas (cómo saber si la falla es general, cómo probar, etc.).
 */
final class ServiceContent
{
    /**
     * @param array<string,mixed> $service
     * @return array<string,mixed>
     */
    public static function build(array $service): array
    {
        $domain = (string) $service['domain'];
        $name = (string) $service['name'];
        $cat = Categories::get((string) $service['category']);
        $spec = self::specific()[$domain] ?? [];

        $rep = static fn (string $t): string => strtr($t, ['{n}' => $name, '{d}' => $domain]);
        $repAll = static fn (array $a): array => array_map(static fn ($x) => is_array($x) ? array_map($rep, $x) : $rep((string) $x), $a);

        $about = $repAll($spec['about'] ?? $cat['about']);
        $editorial = trim((string) ($service['editorial'] ?? ''));
        if ($editorial !== '') {
            $about = array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', $editorial) ?: [])));
        }

        $faq = array_merge($repAll($spec['faq'] ?? []), $repAll($cat['faq']), self::genericFaq($name));

        $defaultTitle = in_array($service['category'], ['banco', 'operadora', 'gobierno', 'transporte', 'ecommerce', 'billetera'], true)
            ? $name . ' no funciona: estado y reportes'
            : '¿' . $name . ' está caído? Estado y reportes ahora';

        return [
            'kind' => $cat['kind'],
            'title' => $spec['title'] ?? $defaultTitle,
            'h1' => $spec['h1'] ?? ($spec['title'] ?? $defaultTitle),
            'meta' => $spec['meta'] ?? self::defaultMeta($name),
            'about' => $about,
            'uses' => $repAll($spec['uses'] ?? $cat['uses']),
            'platforms' => $repAll($spec['platforms'] ?? $cat['platforms']),
            'problems' => $repAll($spec['problems'] ?? $cat['problems']),
            'troubleshooting' => self::troubleshooting($name),
            'interpret' => self::interpretation($name),
            'local_vs_general' => self::localVsGeneral($name),
            'faq' => $faq,
            'official_note' => $spec['official_note'] ?? null,
        ];
    }

    private static function defaultMeta(string $name): string
    {
        return str_limit('Comprobá en segundos si ' . $name . ' funciona. Mirá el estado, el tiempo de respuesta y los reportes de otros usuarios en Argentina de las últimas 24 horas.', 158);
    }

    /** @return array<int,array{0:string,1:string}> */
    private static function troubleshooting(string $n): array
    {
        return [
            ['Revisá tu conexión', 'Abrí otro sitio, por ejemplo google.com.ar. Si tampoco carga, el problema está en tu conexión y no en ' . $n . '.'],
            ['Cambiá de red', 'Probá con datos móviles si estabas con Wi‑Fi, o al revés. Si funciona con una red y con la otra no, la falla está en tu router o en tu proveedor de internet.'],
            ['Reiniciá el módem o router', 'Desenchufalo 30 segundos y volvé a conectarlo. Resuelve muchos cortes parciales y problemas de DNS.'],
            ['Descartá un problema de DNS', 'Si otros sitios abren pero ' . $n . ' no, probá cambiar el DNS del dispositivo a 1.1.1.1 o 8.8.8.8. En computadoras con Windows podés ejecutar "ipconfig /flushdns" para limpiar la caché.'],
            ['Probá en otro dispositivo o navegador', 'Si funciona en otro equipo o en una ventana de incógnito, el inconveniente está en la aplicación, la caché o una extensión.'],
            ['Actualizá o reinstalá la aplicación', 'Instalá la última versión desde la tienda oficial. Si ya está actualizada, limpiá la caché o volvé a instalarla.'],
            ['Desactivá VPN, proxy y bloqueadores', 'Pueden interferir con la conexión. Desactivalos un momento para comprobar.'],
            ['Verificá la fecha y la hora del dispositivo', 'Si están mal configuradas, el navegador puede rechazar el certificado de seguridad y mostrar errores de conexión.'],
        ];
    }

    /** @return array<int,string> */
    private static function interpretation(string $n): array
    {
        return [
            'Un pico de reportes por encima de la actividad habitual (la línea punteada del gráfico) es la señal más fuerte de que algo está pasando con ' . $n . '.',
            'Si los reportes se concentran en un solo tipo de problema, por ejemplo "No puedo iniciar sesión", probablemente falle esa función y no todo el servicio.',
            'Los reportes por zona ayudan a distinguir una falla localizada, de una operadora o una región, de una caída general. Los puntos del mapa son aproximados y están agrupados por ciudad.',
            'Pocos reportes aislados no son una caída. Los reportes son enviados por personas, no están verificados y pueden deberse a problemas propios de cada conexión.',
            'Si el estado indica que el servidor responde pero hay muchos reportes, puede tratarse de una falla parcial: el sitio abre, pero alguna función no anda.',
        ];
    }

    /** @return array<int,array{0:string,1:string,2:string}> filas: aspecto, falla local, falla general */
    private static function localVsGeneral(string $n): array
    {
        return [
            ['Quiénes lo notan', 'Solo vos o personas de tu red.', 'Muchos usuarios en distintas ciudades y operadoras.'],
            ['Otros sitios', 'También fallan o van lentos.', 'Funcionan con normalidad.'],
            ['Otro dispositivo o red', 'Con datos móviles o en otro equipo ' . $n . ' anda.', 'Falla en todos los dispositivos y redes.'],
            ['Reportes en Todo Acesso', 'Pocos o ninguno.', 'Aumento marcado respecto de la actividad habitual.'],
            ['Qué hacer', 'Reiniciá el router, cambiá de red o actualizá la app.', 'Esperar y reintentar más tarde; la solución depende del servicio.'],
        ];
    }

    /** @return array<int,array{0:string,1:string}> */
    private static function genericFaq(string $n): array
    {
        return [
            ['¿Cómo sé si ' . $n . ' está caído para todos o solo para mí?', 'Mirá el estado de arriba y el gráfico de reportes. Si hay un aumento claro de reportes en distintas zonas, es probable que la falla sea general. Si casi nadie reporta, probá los pasos de esta página: suele ser un problema de tu conexión, tu dispositivo o tu cuenta.'],
            ['¿Por qué el estado dice que el servidor responde pero a mí no me funciona?', 'Nuestra verificación comprueba que el servidor devuelva una respuesta, pero no puede revisar cada función ni iniciar sesión con una cuenta. Un servicio puede responder y, aun así, tener fallas parciales, o ser inaccesible para tu zona u operadora.'],
            ['¿Los reportes son oficiales?', 'No. Son enviados de forma anónima por personas que usan ' . $n . ' y no están verificados. Sirven como indicio de lo que está pasando, no como confirmación oficial.'],
            ['¿Todo Acesso está relacionado con ' . $n . '?', 'No. Todo Acesso es un sitio independiente y no tiene vínculo con ' . $n . ' ni con su empresa. Las marcas pertenecen a sus respectivos titulares.'],
        ];
    }

    /** Textos específicos de servicios prioritarios (clave: dominio). */
    private static function specific(): array
    {
        return [
            'whatsapp.com' => [
                'title' => '¿WhatsApp está caído? Estado y problemas ahora',
                'h1' => '¿WhatsApp está caído? Estado y problemas ahora',
                'meta' => 'Comprobá si WhatsApp está caído o no funciona. Mirá el estado del servicio, los reportes de usuarios en Argentina y cómo saber si la falla es general o tuya.',
                'about' => [
                    'WhatsApp es la aplicación de mensajería más usada en Argentina. Permite enviar mensajes, audios, fotos, videos y documentos, hacer llamadas y videollamadas, y armar grupos, todo a través de internet.',
                    'Cuando WhatsApp falla, se nota enseguida: los mensajes se quedan con el relojito, no se descargan las fotos o la aplicación queda en "Conectando…". En esta página podés comprobar si el servidor responde y ver cuántas personas reportan problemas en este momento.',
                ],
                'uses' => ['Chatear con contactos, grupos y comunidades.', 'Enviar audios, fotos, videos, documentos y ubicación.', 'Hacer llamadas y videollamadas.', 'Atender clientes con WhatsApp Business.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'WhatsApp Web, desde el navegador.', 'Aplicación de escritorio para Windows y Mac.', 'WhatsApp Business para empresas.'],
                'problems' => [
                    ['Mensajes con el relojito', 'El mensaje no sale. Puede ser tu conexión o una falla de WhatsApp: si a tus contactos también les pasa, es general.'],
                    ['No se descargan fotos ni audios', 'Los archivos quedan cargando o aparece el aviso "Descarga fallida". Suele ser un problema de almacenamiento o de red.'],
                    ['Estado "Conectando…" permanente', 'La aplicación no logra comunicarse con los servidores. Probá con otra red antes de asumir que es una caída.'],
                    ['WhatsApp Web no sincroniza', 'Pide escanear el QR de nuevo o no carga los chats. Revisá que el celular tenga conexión.'],
                    ['Código de verificación que no llega', 'El SMS o la llamada de verificación demoran o no llegan; probá solicitar el código por llamada.'],
                ],
                'faq' => [
                    ['¿Por qué WhatsApp no envía mis mensajes?', 'Si ves un relojito, tu celular no logró entregar el mensaje al servidor. Revisá tu conexión, probá con datos móviles y, si el problema sigue, consultá si otras personas lo reportan acá.'],
                    ['¿WhatsApp Web no funciona pero el celular sí?', 'WhatsApp Web necesita que el celular esté conectado a internet. Cerrá sesión, volvé a vincular el dispositivo y actualizá la aplicación.'],
                    ['¿Cuánto suelen durar las caídas de WhatsApp?', 'Depende del origen. Algunas fallas parciales se resuelven en minutos y otras pueden extenderse por horas. Los reportes en esta página se actualizan en las últimas 24 horas.'],
                ],
            ],
            'instagram.com' => [
                'title' => '¿Instagram no funciona? Revisá el estado del servicio',
                'h1' => '¿Instagram no funciona? Revisá el estado del servicio',
                'meta' => 'Comprobá si Instagram no funciona o está caído. Mirá el estado, los reportes de usuarios en Argentina y cómo resolver fallas en el feed, historias o mensajes.',
                'about' => [
                    'Instagram es una red social centrada en fotos, videos cortos y mensajes. Se usa para publicar, seguir a otras personas y marcas, ver historias y reels, y chatear por mensajes directos.',
                    'Si Instagram no carga el feed, no te deja subir historias o no abre la aplicación, esta página te ayuda a saber si la falla es general: comprobamos si el servicio responde y mostramos los reportes de usuarios de las últimas 24 horas.',
                ],
                'uses' => ['Ver y publicar fotos, reels e historias.', 'Chatear por mensajes directos.', 'Seguir marcas, tiendas y creadores.', 'Gestionar cuentas profesionales y anuncios.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'Sitio web para computadora.', 'Versión web para celulares.'],
                'problems' => [
                    ['El feed no actualiza', 'Aparece el mensaje "No se pudo actualizar el feed" o queda en blanco.'],
                    ['No se pueden subir historias o reels', 'La publicación queda cargando o falla al finalizar la subida.'],
                    ['Mensajes directos con demoras', 'Los chats no cargan o los mensajes tardan en entregarse.'],
                    ['Cierre de sesión inesperado', 'La app pide iniciar sesión otra vez o solicita verificar la identidad.'],
                    ['Fallas tras una actualización', 'Después de actualizar, la aplicación se cierra sola o no abre.'],
                ],
                'faq' => [
                    ['¿Por qué Instagram no me deja subir una historia?', 'Puede deberse a una conexión inestable, a falta de espacio o a una falla del servicio. Probá con otra red, actualizá la app y, si hay muchos reportes en esta página, esperá un rato.'],
                    ['¿Instagram no carga el feed pero otras apps sí?', 'Probá cerrar y volver a abrir la aplicación, limpiar la caché o reinstalarla. Si muchos usuarios reportan lo mismo, se trata de una falla del servicio.'],
                    ['¿Qué significa "Intentá de nuevo más tarde"?', 'Instagram limita temporalmente acciones muy repetidas o muestra ese aviso cuando tiene problemas internos. Esperar un rato suele resolverlo.'],
                ],
            ],
            'chatgpt.com' => [
                'title' => '¿ChatGPT está caído en Argentina?',
                'h1' => '¿ChatGPT está caído en Argentina? Estado y reportes',
                'meta' => 'Verificá si ChatGPT está caído en Argentina. Mirá el estado del sitio, los reportes de usuarios y qué hacer ante errores, respuestas cortadas o lentitud.',
                'about' => [
                    'ChatGPT es un asistente de inteligencia artificial desarrollado por OpenAI. Permite conversar, redactar y corregir textos, traducir, resumir documentos y ayudar con programación o estudio.',
                    'Por la gran cantidad de personas que lo usan, es habitual que aparezcan errores o lentitud en momentos de alta demanda. Acá podés comprobar si el sitio responde y ver si otras personas en Argentina reportan los mismos problemas.',
                ],
                'uses' => ['Redactar, corregir y traducir textos.', 'Resumir documentos y explicar temas.', 'Ayudar con programación y análisis de datos.', 'Generar ideas y planificar tareas.'],
                'platforms' => ['Sitio web chatgpt.com.', 'Aplicaciones para Android y iPhone.', 'Aplicaciones de escritorio, según el sistema operativo.'],
                'problems' => [
                    ['"Something went wrong" o errores genéricos', 'Aparecen mensajes de error sin detalle. Recargar la página o iniciar un chat nuevo suele ayudar.'],
                    ['Respuestas que se cortan', 'La respuesta se detiene a la mitad. Puede deberse a conexión inestable o a alta demanda.'],
                    ['Mensajes de alta demanda', 'El servicio avisa que tiene mucha demanda o que alcanzaste un límite de uso.'],
                    ['No se puede iniciar sesión', 'El acceso falla en bucle o se cierra la sesión. Probá borrar las cookies del sitio.'],
                    ['Lentitud general', 'Las respuestas tardan mucho más de lo normal en aparecer.'],
                ],
                'faq' => [
                    ['¿Por qué ChatGPT dice "Something went wrong"?', 'Es un error genérico. Recargá la página, iniciá un chat nuevo o cerrá y abrí sesión. Si muchos usuarios lo reportan acá al mismo tiempo, es una falla del servicio.'],
                    ['¿ChatGPT tiene una página oficial de estado?', 'Sí, OpenAI publica el estado de sus servicios en status.openai.com. Es la fuente oficial; Todo Acesso complementa con reportes de usuarios en Argentina.'],
                    ['¿Por qué ChatGPT va lento a veces?', 'La demanda varía durante el día. En horarios de mucho uso o ante fallas parciales, las respuestas pueden demorar más.'],
                ],
                'official_note' => 'La información oficial sobre el estado del servicio la publica OpenAI en status.openai.com.',
            ],
            'mercadolibre.com.ar' => [
                'title' => 'Mercado Libre no funciona: estado y reportes',
                'h1' => '¿Mercado Libre no funciona? Estado y reportes hoy',
                'meta' => 'Comprobá si Mercado Libre no funciona o está caído en Argentina. Mirá el estado, los reportes y soluciones a fallas al comprar o iniciar sesión.',
                'about' => [
                    'Mercado Libre es la plataforma de comercio electrónico más usada de Argentina. Reúne publicaciones de millones de vendedores y ofrece envíos, medios de pago y mensajería entre compradores y vendedores.',
                    'Una falla puede afectar distintas etapas: la búsqueda, el carrito, el pago o el seguimiento del envío. Por eso, además del estado general, es útil ver qué tipo de problemas reportan otros usuarios.',
                ],
                'uses' => ['Buscar y comprar productos nuevos y usados.', 'Publicar y vender artículos.', 'Seguir envíos y devoluciones.', 'Contactar a compradores y vendedores.'],
                'platforms' => ['Sitio web mercadolibre.com.ar.', 'Aplicación para Android y iPhone.', 'Herramientas para vendedores.'],
                'problems' => [
                    ['El sitio carga lento o no abre', 'Las búsquedas y publicaciones tardan o muestran errores.'],
                    ['Fallas al pagar', 'El pago queda procesando o se rechaza sin motivo aparente.'],
                    ['No llegan los códigos de verificación', 'No podés ingresar porque el código por SMS o correo no llega.'],
                    ['Preguntas y mensajes que no se envían', 'Las respuestas a compradores quedan sin publicar.'],
                    ['Seguimiento de envíos desactualizado', 'El estado del paquete no cambia aunque haya novedades.'],
                ],
                'faq' => [
                    ['¿Por qué Mercado Libre no me deja pagar?', 'Puede ser un problema del medio de pago, de la conexión o del servicio. Probá otro medio, revisá la conexión y consultá si otros usuarios reportan problemas de pagos acá.'],
                    ['¿Qué hago si Mercado Libre no carga en la aplicación?', 'Cerrá y abrí la app, actualizala o limpiá la caché. Si el sitio web tampoco abre desde otra red, es probable que sea una falla general.'],
                    ['¿Mercado Libre y Mercado Pago son lo mismo?', 'Son servicios distintos del mismo grupo. Podés consultar el estado de Mercado Pago en su propia página dentro de Todo Acesso.'],
                ],
            ],
            'mercadopago.com.ar' => [
                'title' => 'Mercado Pago no funciona: estado y reportes',
                'h1' => '¿Mercado Pago no funciona? Estado y reportes ahora',
                'meta' => 'Verificá si Mercado Pago no funciona o está caído en Argentina. Mirá el estado, los reportes de usuarios y qué hacer ante fallas en transferencias, QR o la app.',
                'about' => [
                    'Mercado Pago es una billetera virtual y plataforma de pagos muy utilizada en Argentina. Permite transferir dinero, pagar con QR, pagar servicios, invertir y gestionar tarjetas desde el celular.',
                    'Como se usa en comercios y para operaciones del día a día, una falla se percibe de inmediato. Acá podés ver si el servicio responde y qué problemas reportan otros usuarios antes de asumir que el inconveniente es de tu cuenta.',
                ],
                'uses' => ['Transferir dinero por alias o CVU.', 'Pagar con QR en comercios.', 'Pagar servicios e impuestos.', 'Consultar saldo y movimientos.'],
                'platforms' => ['Aplicación Mercado Pago para Android y iPhone.', 'Sitio web mercadopago.com.ar.', 'Herramientas de cobro para comercios.'],
                'problems' => [
                    ['La aplicación no abre o no muestra el saldo', 'Queda cargando o muestra un error temporal.'],
                    ['Transferencias pendientes', 'La operación no se confirma o demora en acreditarse.'],
                    ['QR que no procesa el pago', 'Se lee el código pero la operación falla o se demora.'],
                    ['Problemas de acceso y verificación', 'No llega el código o la validación de identidad no avanza.'],
                ],
                'faq' => [
                    ['Hice una transferencia y no figura, ¿qué hago?', 'No repitas la operación enseguida. Esperá unos minutos, revisá el comprobante en la sección de movimientos y, si sigue pendiente, contactá a soporte desde la aplicación.'],
                    ['¿Mercado Pago está caído si no puedo pagar con QR?', 'No necesariamente. Puede fallar el comercio, tu conexión o el servicio. Mirá si hay un aumento de reportes de "Pagos o compras" en el gráfico.'],
                    ['¿Es seguro seguir intentando pagar?', 'Si una operación queda pendiente, evitá reintentar varias veces para no duplicar pagos. Confirmá primero el estado del movimiento.'],
                ],
            ],
            'facebook.com' => [
                'title' => '¿Facebook está caído? Estado y problemas ahora',
                'h1' => '¿Facebook está caído? Estado y problemas ahora',
                'meta' => 'Comprobá si Facebook está caído o no funciona. Mirá el estado del servicio y los reportes de usuarios en Argentina de las últimas 24 horas.',
                'about' => [
                    'Facebook es una de las redes sociales más antiguas y usadas. Se utiliza para compartir publicaciones, fotos y videos, participar en grupos, usar Marketplace y chatear por Messenger.',
                    'Si no podés ingresar, el muro no carga o Messenger no responde, esta página te muestra si el servidor responde y si otras personas están reportando el mismo problema.',
                ],
                'uses' => ['Publicar y ver contenidos de amigos y páginas.', 'Participar en grupos y eventos.', 'Comprar y vender en Marketplace.', 'Chatear por Messenger.'],
                'platforms' => ['Aplicación para Android y iPhone.', 'Sitio web facebook.com.', 'Aplicación Messenger.'],
                'problems' => [
                    ['El muro no carga', 'Se muestran publicaciones viejas o aparece un error de conexión.'],
                    ['Messenger no envía mensajes', 'Los mensajes quedan pendientes o no se muestran.'],
                    ['Problemas con Marketplace', 'No cargan las publicaciones o no se puede contactar a los vendedores.'],
                    ['Inicio de sesión fallido', 'La cuenta pide verificación o rechaza la contraseña.'],
                ],
                'faq' => [
                    ['¿Facebook no carga solo en mi celular?', 'Probá desde el navegador de una computadora o con otra red. Si funciona, limpiá la caché de la aplicación o reinstalala.'],
                    ['¿Por qué Messenger no envía mis mensajes?', 'Puede ser un problema de conexión o una falla puntual del servicio. Revisá los reportes de esta página para saber si otras personas tienen el mismo inconveniente.'],
                ],
            ],
            'bna.com.ar' => [
                'title' => 'Banco Nación no funciona: estado y reportes',
                'h1' => '¿Banco Nación no funciona? Estado del home banking',
                'meta' => 'Verificá si el sitio del Banco Nación (BNA) funciona. Mirá el estado, los reportes de usuarios en Argentina y qué hacer si no podés ingresar al home banking.',
                'about' => [
                    'El Banco de la Nación Argentina es el mayor banco público del país. Ofrece cuentas, tarjetas, préstamos y servicios digitales a través de su sitio web, home banking y aplicación móvil.',
                    'Esta página te permite comprobar si el sitio institucional responde y ver los reportes de otros usuarios. Tené en cuenta que el home banking puede tener mantenimiento programado, sobre todo de madrugada y los fines de semana.',
                ],
                'faq' => [
                    ['¿Cómo sé si el home banking del Banco Nación está en mantenimiento?', 'El banco suele avisar las ventanas de mantenimiento en su sitio o en su aplicación. Consultá siempre sus canales oficiales para confirmarlo.'],
                    ['¿Esta página es del Banco Nación?', 'No. Todo Acesso es independiente del Banco de la Nación Argentina. Para operar, usá solamente las direcciones y la aplicación oficiales del banco.'],
                ],
            ],
            'netflix.com' => [
                'title' => '¿Netflix está caído? Estado y problemas ahora',
                'h1' => '¿Netflix está caído? Estado y problemas ahora',
                'meta' => 'Comprobá si Netflix está caído o no funciona. Mirá el estado del servicio y los reportes de usuarios en Argentina y cómo resolver fallas de reproducción.',
                'faq' => [
                    ['¿Netflix no carga en mi Smart TV?', 'Reiniciá el televisor, cerrá sesión en la aplicación y volvé a ingresar. Si en el celular funciona, el problema suele estar en la TV o en la red del hogar.'],
                    ['¿Qué significan los códigos de error de Netflix?', 'Netflix muestra códigos para identificar el tipo de falla. Tomá nota del código y buscalo en el centro de ayuda oficial: indica si el origen es la conexión, el dispositivo o el servicio.'],
                ],
            ],
            'youtube.com' => [
                'title' => '¿YouTube está caído? Estado y problemas ahora',
                'h1' => '¿YouTube está caído? Estado y problemas ahora',
                'meta' => 'Comprobá si YouTube está caído o no funciona. Mirá el estado del servicio y los reportes de usuarios en Argentina de las últimas 24 horas.',
                'faq' => [
                    ['¿Por qué YouTube se queda cargando?', 'Puede ser conexión lenta, caché saturada o una falla del servicio. Probá bajar la calidad de reproducción, actualizar la app y reiniciar el router.'],
                ],
            ],
        ];
    }
}
