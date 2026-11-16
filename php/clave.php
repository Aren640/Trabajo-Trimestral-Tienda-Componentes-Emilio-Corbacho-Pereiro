<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderClave($codigo, $mensaje)
{
    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

// La cuenta sale de la sesion no del formulario
if (!isset($_SESSION['id'])) {
    responderClave(401, 'Inicia sesion para cambiar tu contraseña.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderClave(405, 'Utiliza el formulario de contraseña.');
}

$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
$protocolo = 'http://';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $protocolo = 'https://';
}
if ($origen !== '' && $origen !== $protocolo . ($_SERVER['HTTP_HOST'] ?? '')) {
    responderClave(403, 'La peticion debe realizarse desde VEXORA.');
}

foreach (['claveActual', 'claveNueva', 'confirmarClave'] as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        responderClave(422, 'Completa los tres campos de contraseña.');
    }
}
$clave = $_POST['claveNueva'];
if (strlen($clave) < 8 || strlen($clave) > 40) {
    responderClave(422, 'La contraseña nueva debe tener entre 8 y 40 caracteres.');
}
if ($clave !== $_POST['confirmarClave']) {
    responderClave(422, 'Las contraseñas nuevas no coinciden.');
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $usuarios = $base->selectCollection('usuarios');
    $id = new MongoDB\BSON\ObjectId($_SESSION['id']);
    $usuario = $usuarios->findOne(['_id' => $id]);
    if (!$usuario || !password_verify($_POST['claveActual'], $usuario['clave'])) {
        responderClave(401, 'La contraseña actual no es correcta.');
    }

    $hash = password_hash($clave, PASSWORD_DEFAULT);
    // Comparar el hash anterior evita pisar otro cambio de contraseña
    $cambio = $usuarios->updateOne(
        ['_id' => $id, 'clave' => $usuario['clave']],
        ['$set' => ['clave' => $hash]]
    );
    if ($cambio->getMatchedCount() === 0) {
        responderClave(409, 'La contraseña ya ha cambiado. Vuelve a intentarlo con la actual.');
    }

    // Renovar la sesion despues de cambiar la contraseña
    session_regenerate_id(true);
    responderClave(200, 'Contraseña actualizada correctamente.');
} catch (Throwable $error) {
    responderClave(500, 'No se pudo cambiar la contraseña. Intentalo mas tarde.');
}
