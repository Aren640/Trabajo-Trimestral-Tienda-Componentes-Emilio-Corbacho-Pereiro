<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderAdmin($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!isset($_SESSION['id'])) {
    responderAdmin(401, ['mensaje' => 'Inicia sesion con una cuenta de administrador.']);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$seccion = $_GET['seccion'] ?? 'pedidos';

if ($metodo !== 'GET' && $metodo !== 'POST') {
    header('Allow: GET, POST');
    responderAdmin(405, ['mensaje' => 'Metodo no permitido.']);
}

if (!in_array($seccion, ['panel', 'pedidos', 'productos'], true)) {
    responderAdmin(400, ['mensaje' => 'Seccion no valida.']);
}

if ($metodo === 'POST') {
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $protocolo = 'http://';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocolo = 'https://';
    }
    if ($origen !== '' && $origen !== $protocolo . ($_SERVER['HTTP_HOST'] ?? '')) {
        responderAdmin(403, ['mensaje' => 'La peticion debe realizarse desde VEXORA.']);
    }
    if ($seccion !== 'productos') {
        responderAdmin(405, ['mensaje' => 'Metodo no permitido.']);
    }
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $idUsuario = new MongoDB\BSON\ObjectId($_SESSION['id']);
    $usuario = $base->usuarios->findOne(['_id' => $idUsuario]);

    // Comprobar el permiso en MongoDB no solo ocultar el boton del panel
    if (!$usuario || ($usuario['admin'] ?? false) !== true) {
        responderAdmin(403, ['mensaje' => 'No tienes permiso para acceder al panel.']);
    }

    if ($metodo === 'GET' && $seccion !== 'pedidos') {
        responderAdmin(200, ['nombre' => $usuario['nombre']]);
    }

    if ($seccion === 'productos') {
        foreach (['nombre', 'precio', 'categoria', 'imagen', 'stock'] as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                responderAdmin(422, ['mensaje' => 'Completa todos los campos del producto.']);
            }
        }

        $nombre = trim($_POST['nombre']);
        $imagen = trim($_POST['imagen']);
        $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
        $categorias = ['Videojuegos', 'Consolas', 'Accesorios', 'Perifericos', 'PC Gaming', 'Merchandising', 'Regalos'];

        if ($nombre === '' || strlen($nombre) > 120) {
            responderAdmin(422, ['mensaje' => 'Escribe un nombre de hasta 120 caracteres.']);
        }
        if ($precio === false || $precio <= 0 || $precio > 999999.99 || $precio !== round($precio, 2)) {
            responderAdmin(422, ['mensaje' => 'El precio debe ser positivo, con un maximo de dos decimales y de 999999,99 euros.']);
        }
        if ($stock === false || $stock < 0 || $stock > 1000000) {
            responderAdmin(422, ['mensaje' => 'El stock debe ser un entero entre 0 y 1000000.']);
        }
        if (!in_array($_POST['categoria'], $categorias, true)) {
            responderAdmin(422, ['mensaje' => 'Selecciona una categoria disponible.']);
        }
        $esquema = parse_url($imagen, PHP_URL_SCHEME);
        $esquema = strtolower((string) $esquema);
        if (strlen($imagen) > 2048 || !filter_var($imagen, FILTER_VALIDATE_URL) || !in_array($esquema, ['http', 'https'], true)) {
            responderAdmin(422, ['mensaje' => 'Escribe una URL de imagen valida que empiece por http:// o https://.']);
        }

        $producto = $base->productos->insertOne([
            'nombre' => $nombre,
            'precio' => $precio,
            'categoria' => $_POST['categoria'],
            'imagen' => $imagen,
            'stock' => $stock,
            'destacado' => ($_POST['destacado'] ?? '') === '1'
        ]);
        responderAdmin(201, ['mensaje' => 'Producto creado correctamente.', 'id' => (string) $producto->getInsertedId()]);
    }

    $pedidos = $base->pedidos->find([], ['sort' => ['fecha' => -1, '_id' => -1]]);
    $lista = [];
    foreach ($pedidos as $pedido) {
        $productos = [];
        foreach ($pedido['productos'] as $producto) {
            $productos[] = [
                'nombre' => $producto['nombre'],
                'cantidad' => $producto['cantidad'],
                'precio' => $producto['precio']
            ];
        }

        $lista[] = [
            'id' => (string) $pedido['_id'],
            'nombre' => $pedido['nombre'],
            'correo' => $pedido['correo'],
            'fecha' => $pedido['fecha']->toDateTime()->format('c'),
            'productos' => $productos,
            'total' => $pedido['total'],
            'estado' => $pedido['estado']
        ];
    }

    responderAdmin(200, $lista);
} catch (Throwable $error) {
    responderAdmin(500, ['mensaje' => 'No se pudo completar la operacion. Intentalo mas tarde.']);
}
