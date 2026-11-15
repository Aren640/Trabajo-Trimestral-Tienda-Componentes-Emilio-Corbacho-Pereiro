<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderPedido($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if (!isset($_SESSION['id'])) {
    responderPedido(401, ['mensaje' => 'Inicia sesion para realizar un pedido.']);
}

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'GET' && $metodo !== 'POST') {
    header('Allow: GET, POST');
    responderPedido(405, ['mensaje' => 'Metodo no permitido.']);
}

if ($metodo === 'POST') {
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $protocolo = 'http://';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocolo = 'https://';
    }
    if ($origen !== '' && $origen !== $protocolo . ($_SERVER['HTTP_HOST'] ?? '')) {
        responderPedido(403, ['mensaje' => 'La peticion debe realizarse desde VEXORA.']);
    }
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $usuario = $_SESSION['id'];
    if ($metodo === 'GET') {
        // Mostrar solo los pedidos del usuario que tiene la sesion abierta
        $pedidos = $base->pedidos->find(['usuario' => $usuario], ['sort' => ['fecha' => -1, '_id' => -1]]);
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
                'fecha' => $pedido['fecha']->toDateTime()->format('c'),
                'productos' => $productos,
                'total' => $pedido['total'],
                'estado' => $pedido['estado']
            ];
        }
        responderPedido(200, $lista);
    }
    $sesion = $base->getManager()->startSession();
    // Con & podemos recoger el resultado fuera de la transaccion
    $resultado = [];
    $codigo = 201;

    // Crear el pedido y vaciar el carrito juntos para no duplicar la compra
    MongoDB\with_transaction($sesion, function ($sesion) use ($base, $usuario, &$resultado, &$codigo) {
        $opciones = ['session' => $sesion];
        $carrito = $base->carritos->findOne(['_id' => $usuario], $opciones);
        if (!$carrito || count($carrito['productos']) === 0) {
            $codigo = 409;
            $resultado = ['mensaje' => 'Tu carrito esta vacio. Añade productos antes de confirmar el pedido.'];
            return;
        }

        $cliente = $base->usuarios->findOne(['_id' => new MongoDB\BSON\ObjectId($usuario)], $opciones);
        if (!$cliente) {
            $codigo = 401;
            $resultado = ['mensaje' => 'No se ha encontrado tu cuenta. Vuelve a iniciar sesion.'];
            return;
        }

        $productos = [];
        $total = 0;
        foreach ($carrito['productos'] as $linea) {
            $producto = $base->productos->findOne(['_id' => $linea['id']], $opciones);
            if (!$producto) {
                $codigo = 409;
                $resultado = ['mensaje' => 'Hay un producto que ya no esta disponible. Quitalo del carrito para continuar.'];
                return;
            }

            $precio = (float) $producto['precio'];
            $precio = (int) round($precio * 100);
            $productos[] = [
                'id' => $producto['_id'],
                'nombre' => $producto['nombre'],
                'cantidad' => $linea['cantidad'],
                'precio' => $precio / 100
            ];
            $total += $precio * $linea['cantidad'];
        }

        $pedido = $base->pedidos->insertOne([
            'usuario' => $usuario,
            'nombre' => $cliente['nombre'],
            'correo' => $cliente['correo'],
            'productos' => $productos,
            'total' => $total / 100,
            'estado' => 'pendiente',
            'fecha' => new MongoDB\BSON\UTCDateTime()
        ], $opciones);

        // El stock ya se reservo en el carrito y aqui no se descuenta otra vez
        $base->carritos->updateOne(['_id' => $usuario], ['$set' => ['productos' => []]], $opciones);
        $codigo = 201;
        $resultado = ['mensaje' => 'Pedido enviado correctamente. Esta pendiente de revision.', 'id' => (string) $pedido->getInsertedId()];
    });

    $sesion->endSession();
    responderPedido($codigo, $resultado);
} catch (Throwable $error) {
    responderPedido(500, ['mensaje' => 'No se pudo completar la operacion con los pedidos. Intentalo mas tarde.']);
}
