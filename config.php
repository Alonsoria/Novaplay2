<?php
/**
 * NOVAPLAY — CONFIG.PHP
 * Configuración central de base de datos y correo SMTP.
 * NO modifica ninguna consulta SQL existente.
 */

require_once __DIR__ . '/security.php';

/* ── Credenciales de BD ── */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'novaplay3');
define('DB_CHARSET', 'utf8mb4');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/Novaplay2/Novaplay2');

/* ── Configuración SMTP ── */
define('MAIL_HOST',      'sandbox.smtp.mailtrap.io');
define('MAIL_PORT',      2525);
define('MAIL_USER',      '6c60ef8887a087');
define('MAIL_PASS',      'f8b9f80fca962a');
define('MAIL_FROM',      'noreply@novaplay.com.mx');
define('MAIL_FROM_NAME', 'Novaplay');

/* ── Conexión MySQLi ── */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // En producción NO mostrar detalles del error al usuario
    error_log('DB connection failed: ' . $conn->connect_error);
    http_response_code(503);
    die('Servicio temporalmente no disponible. Intenta más tarde.');
}

$conn->set_charset(DB_CHARSET);
