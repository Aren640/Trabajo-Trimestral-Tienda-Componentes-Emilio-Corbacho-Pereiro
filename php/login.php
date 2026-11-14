<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// La sesion mantiene al usuario identificado entre paginas
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderAcceso($codigo, $mensaje)
{
    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

// GET consulta la sesion y POST comprueba el formulario de acceso
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['id'])) {
        try {
            require_once __DIR__ . '/conexion.php';
            $base = conectarMongoDB();
            $usuarios = $base->selectCollection('usuarios');
            $id = new MongoDB\BSON\ObjectId($_SESSION['id']);
            $usuario = $usuarios->findOne(['_id' => $id]);
            if ($usuario) {
                $_SESSION['nombre'] = $usuario['nombre'];
                // Volver a leer el permiso por si se ha cambiado desde MongoDB
                $_SESSION['admin'] = ($usuario['admin'] ?? false) === true;
            } else {
                $_SESSION = [];
            }
        } catch (Throwable $error) {
            responderAcceso(500, 'No se pudo comprobar la sesion. Intentalo mas tarde.');
        }
    }
    echo json_encode(['nombre' => $_SESSION['nombre'] ?? null, 'admin' => $_SESSION['admin'] ?? false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    responderAcceso(405, 'Utiliza el formulario de acceso.');
}

foreach (['correo', 'clave'] as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        responderAcceso(422, 'Completa el correo y la contraseña.');
    }
}

$correo = strtolower(trim($_POST['correo']));
$clave = $_POST['clave'];

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
    responderAcceso(422, 'Completa el correo y la contraseña.');
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $usuarios = $base->selectCollection('usuarios');
    $usuario = $usuarios->findOne(['correo' => $correo]);

    if (!$usuario || !password_verify($clave, $usuario['clave'])) {
        responderAcceso(401, 'Correo o contraseña incorrectos.');
    }

    // Cambiar el identificador al entrar evita reutilizar una sesion anterior
    session_regenerate_id(true);
    $_SESSION = [
        'id' => (string) $usuario['_id'],
        'nombre' => $usuario['nombre'],
        'admin' => ($usuario['admin'] ?? false) === true
    ];

    responderAcceso(200, 'Sesion iniciada correctamente.');
} catch (Throwable $error) {
    responderAcceso(500, 'No se pudo iniciar sesion. Intentalo mas tarde.');
}
