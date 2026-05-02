<?php
/**
 * NOVAPLAY — CREAR_PAGO.PHP
 * Inicia el flujo PayPal: crea la orden y redirige al usuario a PayPal.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'paypal_helper.php';

require_login();

$uid = (int)$_SESSION['user_id'];

/* Calcular total del carrito */
$result = $conn->query(
    "SELECT SUM(p.precio * c.cantidad) AS total
     FROM carrito c JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
$total = (float)($result ? ($result->fetch_assoc()['total'] ?? 0) : 0);

if ($total <= 0) {
    header('Location: carrito.php');
    exit;
}

/* Obtener access token */
$token = paypal_get_token();
if (!$token) {
    $_SESSION['pago_error'] = 'No se pudo conectar con PayPal. Intenta más tarde.';
    header('Location: carrito.php');
    exit;
}

/* Crear orden */
$order = paypal_create_order($token, $total);
if (!$order) {
    $_SESSION['pago_error'] = 'Error al crear la orden en PayPal.';
    header('Location: carrito.php');
    exit;
}

/* Guardar orden en sesión para verificar en captura */
$_SESSION['paypal_order_id'] = $order['id'];
$_SESSION['paypal_total']    = $total;

/* Encontrar la URL de aprobación */
$approveUrl = '';
foreach ($order['links'] ?? [] as $link) {
    if ($link['rel'] === 'approve') {
        $approveUrl = $link['href'];
        break;
    }
}

if (!$approveUrl) {
    $_SESSION['pago_error'] = 'No se recibió URL de aprobación de PayPal.';
    header('Location: carrito.php');
    exit;
}

/* Redirigir al usuario a PayPal */
header('Location: ' . $approveUrl);
exit;
