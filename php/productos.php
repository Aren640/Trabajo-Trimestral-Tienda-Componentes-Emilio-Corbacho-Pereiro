<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderProductos($codigo, $datos)
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderProductos(405, ['mensaje' => 'Utiliza una peticion GET para consultar los productos.']);
}

$id = $_GET['id'] ?? null;

if ($id !== null && (!is_string($id) || $id === '')) {
    responderProductos(400, ['mensaje' => 'El identificador del producto no es valido.']);
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
            responderProductos(404, ['mensaje' => 'No se ha encontrado el producto.']);
        }

        $datos = [$producto];
    } else {
        foreach (['buscar', 'categoria', 'minimo', 'maximo', 'stock', 'orden', 'destacados'] as $campo) {
            if (isset($_GET[$campo]) && !is_string($_GET[$campo])) {
                responderProductos(422, ['mensaje' => 'Los filtros no son validos.']);
            }
        }

        $filtro = [];
        $buscar = trim($_GET['buscar'] ?? '');
        if (strlen($buscar) > 120) {
            responderProductos(422, ['mensaje' => 'La busqueda no puede superar los 120 caracteres.']);
        }
        if ($buscar !== '') {
            // Buscar lo que se ha escrito sin interpretar los signos como una expresion regular
            $filtro['nombre'] = ['$regex' => preg_quote($buscar), '$options' => 'i'];
        }

        $categoria = $_GET['categoria'] ?? '';
        $categorias = ['Videojuegos', 'Consolas', 'Accesorios', 'Perifericos', 'PC Gaming', 'Merchandising', 'Regalos'];
        if ($categoria !== '') {
            if (!in_array($categoria, $categorias, true)) {
                responderProductos(422, ['mensaje' => 'La categoria no es valida.']);
            }
            $filtro['categoria'] = $categoria;
        }

        $precios = [];
        $minimo = $_GET['minimo'] ?? '';
        $maximo = $_GET['maximo'] ?? '';
        if ($minimo !== '') {
            $minimo = filter_var($minimo, FILTER_VALIDATE_FLOAT);
            if ($minimo === false || $minimo < 0 || !is_finite($minimo)) {
                responderProductos(422, ['mensaje' => 'Los precios deben ser numeros iguales o mayores que cero.']);
            }
            $precios['$gte'] = $minimo;
        }
        if ($maximo !== '') {
            $maximo = filter_var($maximo, FILTER_VALIDATE_FLOAT);
            if ($maximo === false || $maximo < 0 || !is_finite($maximo)) {
                responderProductos(422, ['mensaje' => 'Los precios deben ser numeros iguales o mayores que cero.']);
            }
            $precios['$lte'] = $maximo;
        }
        if ($minimo !== '' && $maximo !== '' && $minimo > $maximo) {
            responderProductos(422, ['mensaje' => 'El precio minimo no puede superar al maximo.']);
        }
        if ($precios) {
            $filtro['precio'] = $precios;
        }

        $stock = $_GET['stock'] ?? '';
        if (!in_array($stock, ['', '0', '1'], true)) {
            responderProductos(422, ['mensaje' => 'El filtro de stock no es valido.']);
        }
        if ($stock === '1') {
            $filtro['stock'] = ['$gt' => 0];
        }
        if (isset($_GET['destacados']) && $_GET['destacados'] === '1') {
            $filtro['destacado'] = true;
        }

        $orden = $_GET['orden'] ?? 'recientes';
        // En MongoDB 1 ordena de menor a mayor y -1 al contrario
        if ($orden === 'recientes') {
            $ordenacion = ['_id' => -1];
        } elseif ($orden === 'precio-asc') {
            $ordenacion = ['precio' => 1, '_id' => -1];
        } elseif ($orden === 'precio-desc') {
            $ordenacion = ['precio' => -1, '_id' => -1];
        } elseif ($orden === 'nombre') {
            $ordenacion = ['nombre' => 1, '_id' => -1];
        } else {
            responderProductos(422, ['mensaje' => 'La ordenacion no es valida.']);
        }
        $opciones = [];
        if (isset($_GET['orden'])) {
            $opciones['sort'] = $ordenacion;
        }
        if ($orden === 'nombre') {
            $opciones['collation'] = ['locale' => 'es', 'strength' => 2];
        }
        $datos = $productos->find($filtro, $opciones);
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
        responderProductos(200, $lista[0]);
    } else {
        responderProductos(200, $lista);
    }
} catch (Throwable $error) {
    responderProductos(500, ['mensaje' => 'No se pudieron cargar los productos. Intentalo mas tarde.']);
}
