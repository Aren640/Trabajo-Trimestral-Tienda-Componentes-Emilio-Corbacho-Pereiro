<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderContacto($codigo, $mensaje)
{
    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderContacto(405, 'Utiliza el formulario de contacto.');
}

// Rechazar el envio si viene de otra web
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
$protocolo = 'http://';
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $protocolo = 'https://';
}
if ($origen !== '' && $origen !== $protocolo . ($_SERVER['HTTP_HOST'] ?? '')) {
    responderContacto(403, 'La peticion debe realizarse desde VEXORA.');
}

foreach (['nombreCompleto', 'correo', 'asunto', 'mensaje'] as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        responderContacto(422, 'Completa todos los campos del formulario.');
    }
}

$nombre = trim($_POST['nombreCompleto']);
$correo = strtolower(trim($_POST['correo']));
$asunto = $_POST['asunto'];
$mensaje = trim($_POST['mensaje']);

if ($nombre === '' || strlen($nombre) > 120) {
    responderContacto(422, 'Escribe un nombre de hasta 120 caracteres.');
}
if (strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderContacto(422, 'Escribe un correo electronico valido.');
}
if (!in_array($asunto, ['productos', 'pedidos', 'envios', 'otros'], true)) {
    responderContacto(422, 'Selecciona un asunto valido.');
}
if ($mensaje === '' || strlen($mensaje) > 5000) {
    responderContacto(422, 'Escribe un mensaje de hasta 5000 caracteres.');
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $mensajes = $base->selectCollection('mensajes');
    $mensajes->insertOne([
        'nombre' => $nombre,
        'correo' => $correo,
        'asunto' => $asunto,
        'mensaje' => $mensaje,
        'fecha' => new MongoDB\BSON\UTCDateTime()
    ]);
    responderContacto(201, 'Mensaje enviado correctamente.');
} catch (Throwable $error) {
    responderContacto(500, 'No se pudo guardar el mensaje. Intentalo mas tarde.');
}
