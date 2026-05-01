<?php
/**
 * NOVAPLAY — LOGOUT_AJAX.PHP
 * Cierra la sesión de forma segura y redirige al inicio.
 */

require_once 'security.php';

/* Destruir sesión de forma segura */
$_SESSION = [];

/* Eliminar cookie de sesión */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header('Location: index.php');
exit;
