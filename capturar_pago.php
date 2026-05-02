<?php
/**
 * NOVAPLAY — CAPTURAR_PAGO.PHP
 * Callback de PayPal después de la aprobación del usuario.
 * Captura el pago, registra el pedido, genera códigos y da cashback.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'paypal_helper.php';

require_login();

$uid     = (int)$_SESSION['user_id'];
$orderId = clean_str($_GET['token'] ?? '');

if (!$orderId || $orderId !== ($_SESSION['paypal_order_id'] ?? '')) {
    header('Location: cancel.php');
    exit;
}

/* $total ya viene con el descuento de puntos aplicado desde crear_pago.php */
$total = (float)($_SESSION['paypal_total'] ?? 0);

/* Capturar la orden */
$token   = paypal_get_token();
$capture = $token ? paypal_capture_order($token, $orderId) : null;

if (!$capture || ($capture['status'] ?? '') !== 'COMPLETED') {
    unset($_SESSION['paypal_order_id'], $_SESSION['paypal_total']);
    header('Location: cancel.php');
    exit;
}

/* ── Obtener items del carrito ANTES de limpiar ── */
$cartItems = [];
$resItems = $conn->query(
    "SELECT c.cantidad, p.id_producto, p.nombre, p.imagen
     FROM carrito c JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
if ($resItems) {
    while ($row = $resItems->fetch_assoc()) $cartItems[] = $row;
}

/* Registrar pedido en BD */
$stmt = $conn->prepare("INSERT INTO pedidos (id_usuario, total, estado) VALUES (?, ?, 'pagado')");
$stmt->bind_param("id", $uid, $total);
$stmt->execute();
$idPedido = $conn->insert_id;
$stmt->close();

/* ── Generar códigos de activación ── */
$codigosCompra = [];

foreach ($cartItems as $item) {
    $qty = (int)$item['cantidad'];
    for ($i = 0; $i < $qty; $i++) {
        $codigo = _nova_gen_code($conn);

        $stmtCode = $conn->prepare(
            "INSERT INTO codigos_activacion
                (id_pedido, id_usuario, id_producto, nombre_producto, imagen_producto, codigo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmtCode->bind_param(
            "iiisss",
            $idPedido, $uid, $item['id_producto'],
            $item['nombre'], $item['imagen'], $codigo
        );
        $stmtCode->execute();
        $stmtCode->close();

        $codigosCompra[] = [
            'nombre' => $item['nombre'],
            'imagen' => $item['imagen'],
            'codigo' => $codigo,
        ];
    }
}

/* Cashback: 10% del total como puntos */
$cashback    = (int)floor($total * 0.10);
$mesCurrent  = date('Y-m');

$stmtMes = $conn->prepare("SELECT puntos_reset_mes FROM usuarios WHERE id_usuario = ?");
$stmtMes->bind_param("i", $uid);
$stmtMes->execute();
$mesBD = $stmtMes->get_result()->fetch_assoc()['puntos_reset_mes'] ?? '';
$stmtMes->close();

if ($mesBD !== $mesCurrent) {
    $stmtR = $conn->prepare("UPDATE usuarios SET puntos = ?, puntos_reset_mes = ? WHERE id_usuario = ?");
    $stmtR->bind_param("isi", $cashback, $mesCurrent, $uid);
    $stmtR->execute();
    $stmtR->close();
} else {
    $stmtC = $conn->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id_usuario = ?");
    $stmtC->bind_param("ii", $cashback, $uid);
    $stmtC->execute();
    $stmtC->close();
}

/* ── Deducir puntos usados (guardados en sesión por crear_pago.php) ── */
$puntosUsadosPP = (int)($_SESSION['puntos_usados_paypal'] ?? 0);
if ($puntosUsadosPP > 0) {
    $stmtDP = $conn->prepare("UPDATE usuarios SET puntos = GREATEST(0, puntos - ?) WHERE id_usuario = ?");
    $stmtDP->bind_param("ii", $puntosUsadosPP, $uid);
    $stmtDP->execute();
    $stmtDP->close();
    unset($_SESSION['puntos_usados'], $_SESSION['puntos_usados_paypal']);
}

/* Limpiar carrito */
$conn->query("DELETE FROM carrito WHERE id_usuario = $uid");

/* Notificación */
try {
    $msg    = "Pago de $$total procesado con PayPal. Ganaste $cashback puntos de cashback.";
    $stmtN  = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msg);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* Limpiar sesión de pago */
unset($_SESSION['paypal_order_id'], $_SESSION['paypal_total']);

/* ── Enviar correo de confirmación ── */
try {
    $stmtMail = $conn->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
    $stmtMail->bind_param("i", $uid);
    $stmtMail->execute();
    $userMail = $stmtMail->get_result()->fetch_assoc();
    $stmtMail->close();

    if (!empty($userMail['email'])) {
        $mailTo      = $userMail['email'];
        $mailSubject = "=?UTF-8?B?" . base64_encode("Compra realizada con exito!") . "?=";
        $mailBody    = "Hola " . $userMail['nombre'] . ",\n\n";
        $mailBody   .= "Tus codigos se han generado con exito. Disfrutalos!\n\n";
        foreach ($codigosCompra as $item) {
            $mailBody .= $item['nombre'] . ': ' . $item['codigo'] . "\n";
        }
        $mailBody   .= "\n-- Novaplay.com.mx";
        $mailHeaders  = "From: Novaplay <noreply@novaplay.com.mx>\r\n";
        $mailHeaders .= "Reply-To: noreply@novaplay.com.mx\r\n";
        $mailHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($mailTo, $mailSubject, $mailBody, $mailHeaders);
    }
} catch (Exception $e) {}

$_SESSION['pago_exitoso']   = true;
$_SESSION['pago_cashback']  = $cashback;
$_SESSION['codigos_compra'] = $codigosCompra;

header('Location: success.php');
exit;

/* ─────────────────────────────────────────────────────────────
   Helper: genera un código alfanumérico de 16 chars único en DB
───────────────────────────────────────────────────────────── */
function _nova_gen_code(mysqli $conn): string {
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
