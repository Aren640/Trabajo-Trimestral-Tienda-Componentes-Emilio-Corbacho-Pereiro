<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Utiliza una peticion GET para consultar los productos.']);
    exit;
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $productos = $base->selectCollection('productos');
    $filtro = [];
    if (isset($_GET['destacados']) && $_GET['destacados'] === '1') {
        $filtro['destacado'] = true;
    }
    $lista = [];

    foreach ($productos->find($filtro) as $producto) {
        $lista[] = [
        'id' => (string) $producto['_id'],
        'nombre' => $producto['nombre'],
        'precio' => (float) $producto['precio'],
        'categoria' => $producto['categoria'],
        'imagen' => $producto['imagen'],
        'stock' => (int) $producto['stock'],
        'destacado' => (bool) ($producto['destacado'] ?? false)
        ];
    }

    echo json_encode($lista);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['mensaje' => 'No se pudieron cargar los productos. Intentalo mas tarde.']);
}
