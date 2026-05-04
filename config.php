<?php
/**
 * NOVAPLAY — CONFIG.PHP
 * Configuración central de base de datos.
 * NO modifica ninguna consulta SQL existente.
 */

require_once __DIR__ . '/security.php';

/* ── Credenciales de BD (idealmente en variables de entorno) ── */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/* ── Conexión MySQLi ── */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // En producción NO mostrar detalles del error al usuario
    error_log('DB connection failed: ' . $conn->connect_error);
    http_response_code(503);
    die('Servicio temporalmente no disponible. Intenta más tarde.');
}

$conn->set_charset(DB_CHARSET);
