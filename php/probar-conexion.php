<?php

// Esta prueba solo se puede ejecutar desde la terminal
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/conexion.php';

try {
    $baseDatos = conectarMongoDB();
    $baseDatos->command(['ping' => 1]);
    echo "Conexion con MongoDB correcta.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "No se pudo conectar con MongoDB. Revisa la configuracion de Atlas.\n");
    exit(1);
}
