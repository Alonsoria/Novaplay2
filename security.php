<?php
/**
 * NOVAPLAY — SECURITY.PHP
 * Módulo centralizado de seguridad.
 * Incluir al inicio de CADA archivo PHP con:  require_once 'security.php';
 */

/* ============================================================
   1. CONFIGURACIÓN SEGURA DE SESIÓN
   ============================================================ */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,          // Solo HTTPS
        'httponly' => true,          // JS no puede leer la cookie
        'samesite' => 'Strict',      // Protege contra CSRF desde otros sitios
    ]);
    session_start();
}

/* Regenerar ID de sesión periódicamente para prevenir secuestro */
if (!isset($_SESSION['_initiated'])) {
    session_regenerate_id(true);
    $_SESSION['_initiated'] = true;
}

/* ============================================================
   2. CABECERAS DE SEGURIDAD HTTP
   ============================================================ */
header("X-Content-Type-Options: nosniff");           // Evita MIME sniffing
header("X-Frame-Options: SAMEORIGIN");               // Evita clickjacking
header("X-XSS-Protection: 1; mode=block");          // Activa protección XSS del navegador
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.paypal.com https://cdn.jsdelivr.net; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
    "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self' https://www.paypal.com https://cdn.jsdelivr.net; " .
    "frame-src https://www.paypal.com; " .
    "object-src 'none';"
);

/* ============================================================
   3. GENERADOR Y VALIDADOR DE TOKEN CSRF
   ============================================================ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Devuelve el campo oculto CSRF listo para insertar en formularios.
 * Uso en HTML:  <?= csrf_field() ?>
 */
function csrf_field(): string {
    $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verifica que el token CSRF del formulario sea válido.
 * Llámala en el handler POST antes de procesar nada.
 * Si falla, termina la ejecución con error 403.
 */
function csrf_verify(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Solicitud inválida. Por favor recarga la página e intenta de nuevo.');
    }
}

/* ============================================================
   4. SANITIZACIÓN DE INPUTS
   ============================================================ */

/**
 * Limpia un string: elimina espacios extremos y escapa caracteres HTML.
 * Úsala para cualquier dato que venga de $_POST, $_GET o $_REQUEST.
 *
 * Ejemplo: $nombre = clean_str($_POST['nombre']);
 */
function clean_str(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y devuelve un entero seguro, o null si no es válido.
 * Ejemplo: $id = clean_int($_GET['id']);
 */
function clean_int(mixed $value): ?int {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return ($filtered !== false) ? (int)$filtered : null;
}

/**
 * Valida un email.
 * Devuelve el email limpio o null si es inválido.
 */
function clean_email(string $value): ?string {
    $email = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    return ($email !== false) ? $email : null;
}

/**
 * Escapa un valor para mostrarlo en HTML sin riesgo de XSS.
 * Úsala al imprimir datos de la BD en el HTML.
 *
 * Ejemplo: echo e($row['nombre']);
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ============================================================
   5. VERIFICADOR DE SESIÓN (login required)
   ============================================================ */

/**
 * Redirige al login si el usuario no ha iniciado sesión.
 * Uso: require_login();  — al inicio de páginas protegidas.
 */
function require_login(string $redirect = 'login.php'): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * ¿Hay un usuario logueado?
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
