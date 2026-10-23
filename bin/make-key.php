<?php
declare(strict_types=1);

// Genera una APP_KEY aleatoria (hex de 64 caracteres).
echo bin2hex(random_bytes(32)), "\n";
