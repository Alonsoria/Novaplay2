<?php
/**
 * NOVAPLAY — SIMULAR_PAGO_OXXO.PHP
 * SANDBOX ONLY — Simula la confirmación de pago en OXXO.
 *
 * Acciones:
 *   1. Verifica que el pedido pertenece al usuario y está 'pendiente_oxxo'.
 *   2. Actualiza estado a 'pagado'.
 *   3. Genera los códigos de activación desde el JSON guardado.
 *   4. Envía correo de confirmación.
 *   5. Redirige a success.php (flujo idéntico a tarjeta/PayPal).
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php#pedidos');
    exit;
}

csrf_verify();

$uid      = (int)$_SESSION['user_id'];
$pedidoId = clean_int($_POST['pedido_id'] ?? null);

if (!$pedidoId) {
    header('Location: perfil.php#pedidos');
    exit;
}

/* ── Verificar pedido ── */
$stmtPed = $conn->prepare(
    "SELECT id_pedido, total, oxxo_items_json
     FROM pedidos WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pendiente_oxxo'"
);
$stmtPed->bind_param("ii", $pedidoId, $uid);
$stmtPed->execute();
$pedido = $stmtPed->get_result()->fetch_assoc();
$stmtPed->close();

if (!$pedido) {
    /* Ya fue procesado o no existe */
    header('Location: perfil.php#pedidos');
    exit;
}

$total     = (float)$pedido['total'];
$cartItems = json_decode($pedido['oxxo_items_json'] ?? '[]', true) ?: [];

/* ── Marcar como pagado ── */
$stmtUpd = $conn->prepare("UPDATE pedidos SET estado = 'pagado' WHERE id_pedido = ?");
$stmtUpd->bind_param("i", $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

/* ── Generar códigos de activación ── */
$codigosCompra = [];
foreach ($cartItems as $item) {
    $qty   = (int)($item['cantidad'] ?? 1);
    $idProd = (int)($item['id_producto'] ?? 0);
    $nombre = $item['nombre'] ?? 'Producto';
    $imagen = $item['imagen'] ?? '';

    for ($i = 0; $i < $qty; $i++) {
        $codigo = _oxxo_gen_code($conn);

        $stmtCode = $conn->prepare(
            "INSERT INTO codigos_activacion
                (id_pedido, id_usuario, id_producto, nombre_producto, imagen_producto, codigo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmtCode->bind_param("iiisss", $pedidoId, $uid, $idProd, $nombre, $imagen, $codigo);
        $stmtCode->execute();
        $stmtCode->close();

        $codigosCompra[] = [
            'nombre' => $nombre,
            'imagen' => $imagen,
            'codigo' => $codigo,
        ];
    }
}

/* ── Cashback (se acredita al confirmar los productos) ── */
$cashback = (int)floor($total * 0.10);

/* ── Notificación interna ── */
try {
    $msg   = "¡Pago OXXO confirmado! Pedido #{$pedidoId}. Confirma tus productos para ganar {$cashback} puntos.";
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msg);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Correo de confirmación ── */
try {
    $stmtMail = $conn->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
    $stmtMail->bind_param("i", $uid);
    $stmtMail->execute();
    $userMail = $stmtMail->get_result()->fetch_assoc();
    $stmtMail->close();

    if (!empty($userMail['email'])) {
        $mailBody  = "Hola {$userMail['nombre']},\n\n";
        $mailBody .= "¡Tu pago OXXO del pedido #{$pedidoId} ha sido confirmado!\n\n";
        $mailBody .= "Tus códigos se han generado. ¡Disfrútalos!\n\n";
        foreach ($codigosCompra as $item) {
            $mailBody .= $item['nombre'] . ': ' . $item['codigo'] . "\n";
        }
        $mailBody .= "\n-- Novaplay.com.mx";

        nova_send_mail(
            $userMail['email'],
            "¡Pago OXXO confirmado! — Pedido #{$pedidoId}",
            $mailBody
        );
    }
} catch (Exception $e) {
    error_log('[Novaplay OXXO] Error al enviar correo confirmación: ' . $e->getMessage());
}

/* ── Limpiar sesión OXXO ── */
unset(
    $_SESSION['oxxo_pedido_id'],
    $_SESSION['oxxo_referencia'],
    $_SESSION['oxxo_barcode_url'],
    $_SESSION['oxxo_expira'],
    $_SESSION['oxxo_total']
);

/* ── Redirigir a success.php (mismo flujo que tarjeta/PayPal) ── */
$_SESSION['pago_exitoso']   = true;
$_SESSION['pago_cashback']  = $cashback;
$_SESSION['pago_pedido_id'] = $pedidoId;

header('Location: success.php');
exit;

/* ── Helper: código de activación único ── */
function _oxxo_gen_code(mysqli $conn): string {
    static $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $code = '';
        for ($i = 0; $i < 16; $i++) {
            $code .= $chars[random_int(0, 35)];
        }
        $stmtChk = $conn->prepare("SELECT id FROM codigos_activacion WHERE codigo = ? LIMIT 1");
        $stmtChk->bind_param("s", $code);
        $stmtChk->execute();
        $stmtChk->store_result();
        $exists = $stmtChk->num_rows > 0;
        $stmtChk->close();
        if (!$exists) return $code;
    }
    return strtoupper(bin2hex(random_bytes(8)));
}
