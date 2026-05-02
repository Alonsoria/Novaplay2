<?php
/**
 * NOVAPLAY — PAYPAL_CONFIG.PHP
 * Credenciales PayPal Sandbox. NO subir a repositorios públicos.
 *
 * Cómo configurar en XAMPP:
 *   Opción A (recomendada): Variables de entorno en httpd.conf o .htaccess:
 *       SetEnv PAYPAL_CLIENT_ID "AxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxXXX"
 *       SetEnv PAYPAL_SECRET    "ExxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxXXX"
 *
 *   Opción B: Definir directamente aquí (solo para desarrollo local):
 *       define('PAYPAL_CLIENT_ID_VAL', 'tu_client_id_sandbox');
 *       define('PAYPAL_SECRET_VAL',    'tu_secret_sandbox');
 */

define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('PAYPAL_SECRET',    getenv('PAYPAL_SECRET')    ?: '');
define('PAYPAL_MODE',      getenv('PAYPAL_MODE')      ?: 'sandbox');

define('PAYPAL_BASE_URL',  PAYPAL_MODE === 'live'
    ? 'https://api-m.paypal.com'
    : 'https://api-m.sandbox.paypal.com');

/* URL base del sitio (para retornos de PayPal) */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('SITE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/novaplay2');
