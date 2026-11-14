<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderCarrito($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

// Cada usuario accede solo a su propio carrito
if (!isset($_SESSION['id'])) {
    responderCarrito(401, ['mensaje' => 'Inicia sesion para utilizar el carrito.']);
}

$usuario = $_SESSION['id'];
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo !== 'GET' && $metodo !== 'POST') {
    responderCarrito(405, ['mensaje' => 'Metodo no permitido.']);
}

if ($metodo === 'POST') {
    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    $protocolo = 'http://';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocolo = 'https://';
    }
    if ($origen !== '' && $origen !== $protocolo . ($_SERVER['HTTP_HOST'] ?? '')) {
        responderCarrito(403, ['mensaje' => 'La peticion debe realizarse desde VEXORA.']);
    }

    $accion = $_POST['accion'] ?? '';
    $id = $_POST['id'] ?? '';
    if (!in_array($accion, ['anadir', 'cambiar', 'eliminar'], true) || !is_string($id) || $id === '') {
        responderCarrito(422, ['mensaje' => 'Los datos del carrito no son validos.']);
    }

    $cantidad = 0;
    if ($accion !== 'eliminar') {
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($cantidad === false) {
            responderCarrito(422, ['mensaje' => 'La cantidad debe ser un numero entero mayor que cero.']);
        }
    }
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $carritos = $base->selectCollection('carritos');
    $productos = $base->selectCollection('productos');

    if ($metodo === 'GET') {
        $carrito = $carritos->findOne(['_id' => $usuario]);
        $lista = [];
        $total = 0;
        $unidades = 0;

        foreach ($carrito['productos'] ?? [] as $linea) {
            $producto = $productos->findOne(['_id' => $linea['id']]);
            // Sumar en centimos para evitar errores con los decimales
            $precio = (float) ($producto['precio'] ?? 0);
            $precio = (int) round($precio * 100);
            $subtotal = $precio * $linea['cantidad'];
            $lista[] = [
                'id' => (string) $linea['id'],
                'nombre' => $producto['nombre'] ?? 'Producto no disponible',
                'imagen' => $producto['imagen'] ?? '',
                'cantidad' => $linea['cantidad'],
                'disponibles' => (int) ($producto['stock'] ?? 0),
                'precio' => $precio / 100,
                'subtotal' => $subtotal / 100
            ];
            $total += $subtotal;
            $unidades += $linea['cantidad'];
        }

        responderCarrito(200, ['productos' => $lista, 'total' => $total / 100, 'unidades' => $unidades]);
    }

    $sesion = $base->getManager()->startSession();
    // Con & podemos recoger el resultado fuera de la transaccion
    $resultado = [];
    // Si falla algo se deshacen la reserva y el cambio del carrito
    MongoDB\with_transaction($sesion, function ($sesion) use ($carritos, $productos, $usuario, $accion, $id, $cantidad, &$resultado) {
        $opciones = ['session' => $sesion];
        $carrito = $carritos->findOne(['_id' => $usuario], $opciones);
        if ($accion !== 'eliminar') {
            $producto = $productos->findOne(['_id' => $id], $opciones);
            // Hay productos con id de texto y otros con ObjectId
            if (!$producto && strlen($id) === 24 && ctype_xdigit($id)) {
                $producto = $productos->findOne(['_id' => new MongoDB\BSON\ObjectId($id)], $opciones);
            }
            if (!$producto) {
                $resultado = ['codigo' => 404, 'mensaje' => 'No se ha encontrado el producto.'];
                return;
            }
            $id = (string) $producto['_id'];
        }

        $nuevo = [];
        $posicion = null;
        $actual = 0;

        foreach ($carrito['productos'] ?? [] as $linea) {
            if ((string) $linea['id'] === $id) {
                $posicion = count($nuevo);
                $actual = $linea['cantidad'];
            }
            $nuevo[] = ['id' => $linea['id'], 'cantidad' => $linea['cantidad']];
        }

        if ($accion === 'eliminar') {
            if ($posicion !== null) {
                $productos->updateOne(
                    ['_id' => $nuevo[$posicion]['id']],
                    ['$inc' => ['stock' => $actual]],
                    $opciones
                );
                array_splice($nuevo, $posicion, 1);
            }

            $carritos->updateOne(['_id' => $usuario], ['$set' => ['productos' => $nuevo]], $opciones);
            $resultado = ['codigo' => 200, 'mensaje' => 'Producto quitado del carrito.'];
            return;
        }

        if ($accion === 'cambiar' && $posicion === null) {
            $resultado = ['codigo' => 404, 'mensaje' => 'El producto ya no esta en tu carrito.'];
            return;
        }

        $nueva = $cantidad;
        if ($accion === 'anadir') {
            $nueva += $actual;
        }
        $diferencia = $nueva - $actual;

        // La diferencia positiva reserva unidades y la negativa las devuelve
        $filtro = ['_id' => $producto['_id']];
        if ($diferencia > 0) {
            $filtro['stock'] = ['$gte' => $diferencia];
        }
        // Comprobar y descontar el stock en la misma operacion evita vender unidades de mas
        $reserva = $productos->updateOne($filtro, ['$inc' => ['stock' => -$diferencia]], $opciones);
        if ($reserva->getMatchedCount() === 0) {
            $resultado = ['codigo' => 409, 'mensaje' => 'No hay suficientes unidades disponibles.'];
            return;
        }

        if ($posicion === null) {
            $nuevo[] = ['id' => $producto['_id'], 'cantidad' => $nueva];
        } else {
            $nuevo[$posicion]['cantidad'] = $nueva;
        }

        $carritos->updateOne(
            ['_id' => $usuario],
            ['$set' => ['productos' => $nuevo]],
            ['session' => $sesion, 'upsert' => true]
        );

        $mensaje = 'Producto añadido al carrito.';
        if ($accion === 'cambiar') {
            $mensaje = 'Cantidad actualizada.';
        }
        $resultado = ['codigo' => 200, 'mensaje' => $mensaje];
    });
    $sesion->endSession();
    responderCarrito($resultado['codigo'], ['mensaje' => $resultado['mensaje']]);
} catch (Throwable $error) {
    responderCarrito(500, ['mensaje' => 'No se pudo actualizar el carrito. Intentalo mas tarde.']);
}
