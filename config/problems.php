<?php
declare(strict_types=1);

/** Tipos de problema que se pueden reportar (clave => etiqueta en español rioplatense). */
return [
    'conexion' => ['label' => 'Problemas de conexión', 'hint' => 'No logro conectarme'],
    'sitio_error' => ['label' => 'El sitio no abre o muestra un error', 'hint' => 'Página en blanco o mensaje de error'],
    'login' => ['label' => 'No puedo iniciar sesión', 'hint' => 'Usuario o contraseña no funcionan'],
    'app' => ['label' => 'La aplicación no funciona', 'hint' => 'Se cierra o no carga'],
    'lentitud' => ['label' => 'Lentitud o interrupciones', 'hint' => 'Anda lento o se corta'],
    'pagos' => ['label' => 'Pagos o compras', 'hint' => 'No puedo pagar o comprar'],
    'otro' => ['label' => 'Otro problema', 'hint' => 'Algo distinto de lo anterior'],
];
