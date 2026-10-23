<?php
declare(strict_types=1);

// Uso: php bin/make-admin-hash.php "contraseña"   (o --generate para crear una contraseña aleatoria)
$arg = $argv[1] ?? '';
if ($arg === '' ) {
    fwrite(STDERR, "Uso: php bin/make-admin-hash.php <contraseña> | --generate\n");
    exit(1);
}
$password = $arg;
if ($arg === '--generate') {
    $password = rtrim(strtr(base64_encode(random_bytes(18)), '+/', 'Ax'), '=');
    echo "Contraseña generada (guardala en un gestor de contraseñas): $password\n";
}
if (strlen($password) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}
echo "ADMIN_PASSWORD_HASH='" . password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]) . "'\n";
