<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Los datos de conexion vienen del .env que carga Docker
function conectarMongoDB()
{
    $uri = getenv('MONGODB_URI');
    $nombreBase = getenv('MONGODB_DATABASE');
    if (!$nombreBase) {
        $nombreBase = 'vexora';
    }

    if (!$uri) {
        throw new RuntimeException('Configura MONGODB_URI en el archivo .env.');
    }

    $cliente = new MongoDB\Client($uri, ['serverSelectionTimeoutMS' => 5000]);

    return $cliente->selectDatabase($nombreBase);
}
