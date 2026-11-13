<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['mensaje' => 'Utiliza una peticion GET para consultar los productos.']);
    exit;
}

$id = $_GET['id'] ?? null;

if ($id !== null && (!is_string($id) || $id === '')) {
    http_response_code(400);
    echo json_encode(['mensaje' => 'El identificador del producto no es valido.']);
    exit;
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $productos = $base->selectCollection('productos');
    if ($id !== null) {
        $producto = $productos->findOne(['_id' => $id]);

        // Hay productos con id de texto y otros con ObjectId
        if (!$producto && strlen($id) === 24 && ctype_xdigit($id)) {
            $idMongo = new MongoDB\BSON\ObjectId($id);
            $producto = $productos->findOne(['_id' => $idMongo]);
        }

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['mensaje' => 'No se ha encontrado el producto.']);
            exit;
        }

        $datos = [$producto];
    } else {
        $filtro = [];
        if (isset($_GET['destacados']) && $_GET['destacados'] === '1') {
            $filtro['destacado'] = true;
        }
        $datos = $productos->find($filtro);
    }

    $lista = [];

    foreach ($datos as $producto) {
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

    if ($id !== null) {
        echo json_encode($lista[0]);
    } else {
        echo json_encode($lista);
    }
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['mensaje' => 'No se pudieron cargar los productos. Intentalo mas tarde.']);
}
