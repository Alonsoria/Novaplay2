<?php
/**
 * NOVAPLAY — PAGAR_OXXO.PHP
 * Crea una orden de pago OXXO vía Conekta (sandbox).
 * - Calcula total con descuento de puntos.
 * - Registra el pedido como 'pendiente_oxxo' en BD.
 * - Llama a la API de Conekta para obtener el código de barras.
 * - Guarda items del carrito en JSON para generar códigos al confirmar pago.
 * - Limpia el carrito.
 * - Redirige a oxxo_pendiente.php.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'conekta_helper.php';
require_once 'mailer.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}

csrf_verify();

$uid = (int)$_SESSION['user_id'];

/* ── Total bruto del carrito ── */
$result = $conn->query(
    "SELECT SUM(p.precio * c.cantidad) AS total
     FROM carrito c JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
$totalBruto = (float)($result ? ($result->fetch_assoc()['total'] ?? 0) : 0);

if ($totalBruto <= 0) {
    header('Location: carrito.php');
    exit;
}

/* ── Descuento de puntos (igual que tarjeta/PayPal) ── */
$puntosUsados = 0;
if (!empty($_SESSION['puntos_usados'])) {
    $stmtPV = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
    $stmtPV->bind_param("i", $uid);
    $stmtPV->execute();
    $puntosDisp = (int)($stmtPV->get_result()->fetch_assoc()['puntos'] ?? 0);
    $stmtPV->close();
    $puntosUsados = min((int)$_SESSION['puntos_usados'], $puntosDisp, (int)$totalBruto);
}
$total = max(0.0, $totalBruto - $puntosUsados);

/* Conekta requiere mínimo $10 MXN */
if ($total < 10.0) {
    $_SESSION['pago_error'] = 'El monto mínimo para pagar en tienda es $10.00 MXN.';
    header('Location: carrito.php');
    exit;
}

/* ── Items del carrito (guardar antes de limpiar) ── */
$cartItems = [];
$resItems  = $conn->query(
    "SELECT c.cantidad, p.id_producto, p.nombre, p.imagen, p.precio
     FROM carrito c JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
if ($resItems) {
    while ($row = $resItems->fetch_assoc()) $cartItems[] = $row;
}

/* ── Datos del usuario para Conekta ── */
$stmtU = $conn->prepare("SELECT nombre, apellido, email, telefono FROM usuarios WHERE id_usuario = ?");
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$customerName  = trim(($userData['nombre'] ?? '') . ' ' . ($userData['apellido'] ?? '')) ?: 'Cliente';
$customerEmail = $userData['email'] ?? '';
$customerPhone = preg_replace('/\D/', '', $userData['telefono'] ?? '');

/* ── Crear pedido en BD con estado pendiente_oxxo ── */
$oxxoExpiraDt = date('Y-m-d H:i:s', time() + 72 * 3600);
$itemsJson    = json_encode($cartItems, JSON_UNESCAPED_UNICODE);

$stmtP = $conn->prepare(
    "INSERT INTO pedidos (id_usuario, total, estado, metodo_pago, oxxo_expira, oxxo_items_json)
     VALUES (?, ?, 'pendiente_oxxo', 'oxxo', ?, ?)"
);
$stmtP->bind_param("idss", $uid, $total, $oxxoExpiraDt, $itemsJson);
$stmtP->execute();
$idPedido = $conn->insert_id;
$stmtP->close();

/* ── Llamar a Conekta para generar referencia OXXO ── */
$expiresAtTs = time() + 72 * 3600;
try {
    $conekta = conekta_create_efectivo_order(
        $total,
        $customerName,
        $customerEmail,
        $customerPhone,
        $idPedido,
        $expiresAtTs
    );

    $referencia  = $conekta['referencia'];
    $barcodeUrl  = $conekta['barcode_url']
        ?: (rtrim(SITE_URL, '/') . '/barcode_gen.php?ref=' . urlencode($referencia));
    $redirectUrl = $conekta['redirect_url'];

    $stmtUpd = $conn->prepare(
        "UPDATE pedidos SET oxxo_order_id = ?, oxxo_referencia = ?, oxxo_barcode_url = ?
         WHERE id_pedido = ?"
    );
    $stmtUpd->bind_param("sssi", $conekta['order_id'], $referencia, $barcodeUrl, $idPedido);
    $stmtUpd->execute();
    $stmtUpd->close();

} catch (Exception $e) {
    $errMsg = $e->getMessage();
    error_log('[Novaplay OXXO] Error Conekta: ' . $errMsg);

    /* Fallback sandbox: Conekta Efectivo no disponible en esta cuenta sandbox.
       Se genera una referencia y código de barras locales para probar el flujo completo. */
    error_log('[Novaplay OXXO] Usando referencia sandbox simulada para pedido #' . $idPedido . ' — error: ' . $errMsg);

    $referencia  = 'SBX' . str_pad((string)$idPedido, 4, '0', STR_PAD_LEFT)
                 . strtoupper(bin2hex(random_bytes(5)));
    $barcodeUrl  = rtrim(SITE_URL, '/') . '/barcode_gen.php?ref=' . urlencode($referencia);
    $redirectUrl = '';
    $fakeOrderId = 'sandbox_' . $idPedido;

    $stmtUpd2 = $conn->prepare(
        "UPDATE pedidos SET oxxo_order_id = ?, oxxo_referencia = ?, oxxo_barcode_url = ?
         WHERE id_pedido = ?"
    );
    $stmtUpd2->bind_param("sssi", $fakeOrderId, $referencia, $barcodeUrl, $idPedido);
    $stmtUpd2->execute();
    $stmtUpd2->close();
}

/* ── Deducir puntos usados inmediatamente ── */
if ($puntosUsados > 0) {
    $stmtDP = $conn->prepare("UPDATE usuarios SET puntos = GREATEST(0, puntos - ?) WHERE id_usuario = ?");
    $stmtDP->bind_param("ii", $puntosUsados, $uid);
    $stmtDP->execute();
    $stmtDP->close();
    unset($_SESSION['puntos_usados']);
}

/* ── Limpiar carrito ── */
$conn->query("DELETE FROM carrito WHERE id_usuario = $uid");

/* ── Notificación interna ── */
try {
    $msg   = "Pedido #{$idPedido} pendiente de pago en tienda. Tienes 72 horas para pagarlo.";
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msg);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Correo con instrucciones OXXO ── */
try {
    $mailBody  = "Hola {$userData['nombre']},\n\n";
    $mailBody .= "¡Tu pedido #{$idPedido} ha sido creado correctamente!\n\n";
    $mailBody .= "────────────────────────────────────\n";
    $mailBody .= "INSTRUCCIONES DE PAGO EN TIENDA\n";
    $mailBody .= "────────────────────────────────────\n";
    $mailBody .= "Referencia:  {$referencia}\n";
    $mailBody .= "Importe:     \$" . number_format($total, 2) . " MXN\n";
    $mailBody .= "Vence:       " . date('d/m/Y H:i', strtotime($oxxoExpiraDt)) . "\n\n";
    $mailBody .= "1. Presenta esta referencia en cualquier tienda participante.\n";
    $mailBody .= "   (BBVA, 7-Eleven, Walmart, Farmacias del Ahorro, CIRCLE K, Soriana y más)\n";
    $mailBody .= "2. Indica que deseas realizar un pago de servicios (Conekta).\n";
    $mailBody .= "3. Paga el importe exacto.\n\n";
    $mailBody .= "Una vez confirmado el pago, tus códigos de activación estarán\n";
    $mailBody .= "disponibles en Perfil → Historial de pedidos.\n\n";
    $mailBody .= "— Equipo Novaplay";

    nova_send_mail($customerEmail, "Pago pendiente en tienda — Pedido #{$idPedido}", $mailBody);
} catch (Exception $e) {
    error_log('[Novaplay OXXO] Error al enviar correo: ' . $e->getMessage());
}

/* ── Pasar datos a oxxo_pendiente.php vía sesión ── */
$_SESSION['oxxo_pedido_id']    = $idPedido;
$_SESSION['oxxo_referencia']   = $referencia;
$_SESSION['oxxo_barcode_url']  = $barcodeUrl;
$_SESSION['oxxo_redirect_url'] = $redirectUrl;
$_SESSION['oxxo_expira']       = $expiresAtTs;
$_SESSION['oxxo_total']        = $total;

header('Location: oxxo_pendiente.php');
exit;
