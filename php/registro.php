<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function responderRegistro($codigo, $mensaje)
{
    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

foreach (['nombre', 'correo', 'clave', 'confirmarClave'] as $campo) {
    if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
        responderRegistro(422, 'Completa todos los campos del formulario.');
    }
}

$nombre = trim($_POST['nombre']);
$correo = strtolower(trim($_POST['correo']));
$clave = $_POST['clave'];

if ($nombre === '') {
    responderRegistro(422, 'Escribe tu nombre.');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderRegistro(422, 'Escribe un correo electronico valido.');
}

if (strlen($clave) < 8 || strlen($clave) > 40) {
    responderRegistro(422, 'La contraseña debe tener entre 8 y 40 caracteres.');
}

if ($clave !== $_POST['confirmarClave']) {
    responderRegistro(422, 'Las contraseñas no coinciden.');
}

try {
    require_once __DIR__ . '/conexion.php';
    $base = conectarMongoDB();
    $usuarios = $base->selectCollection('usuarios');

    // Guardar el hash nunca la contraseña escrita
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    // Las cuentas nuevas son clientes
    $alta = $usuarios->insertOne([
        'nombre' => $nombre,
        'correo' => $correo,
        'clave' => $hash,
        'rol' => 'cliente',
        'fechaRegistro' => new MongoDB\BSON\UTCDateTime()
    ]);

    // Al registrarse el usuario ya queda con la sesion iniciada
    session_regenerate_id(true);
    $_SESSION = [
        'id' => (string) $alta->getInsertedId(),
        'nombre' => $nombre,
        'rol' => 'cliente'
    ];

    responderRegistro(201, 'Cuenta creada correctamente.');
} catch (Throwable $error) {
    // El indice de correo unico devuelve el error 11000 si la cuenta ya existe
    if ($error instanceof MongoDB\Driver\Exception\BulkWriteException && $error->getCode() === 11000) {
        responderRegistro(409, 'Ya existe una cuenta con ese correo.');
    }

    responderRegistro(500, 'No se pudo crear la cuenta. Intentalo mas tarde.');
}
