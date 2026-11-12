<?php

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

session_start(['use_strict_mode' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
// Borrar tanto la sesion como la cookie del navegador
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

header('Location: ../index.html', true, 303);
exit;
