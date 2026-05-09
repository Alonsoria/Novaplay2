<?php
/**
 * NOVAPLAY — PROCESAR_PAGO.PHP
 * Procesa el pago simulado con tarjeta (sandbox).
 * Tokeniza la tarjeta si el usuario lo solicita.
 * Genera códigos de activación por producto comprado.
 * Otorga cashback (10%) y registra el pedido.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}

csrf_verify();

$uid = (int)$_SESSION['user_id'];

/* ── Verificar si usa tarjeta guardada ── */
$savedCardIdRaw = (int)($_POST['saved_card_id_selected'] ?? 0);
$usingSavedCard = false;
$savedCardData  = null;

if ($savedCardIdRaw > 0) {
    $stmtSC = $conn->prepare(
        "SELECT id, ultimos4, marca, expiry FROM tarjetas_guardadas WHERE id = ? AND id_usuario = ?"
    );
    $stmtSC->bind_param("ii", $savedCardIdRaw, $uid);
    $stmtSC->execute();
    $savedCardData = $stmtSC->get_result()->fetch_assoc();
    $stmtSC->close();
    if ($savedCardData) $usingSavedCard = true;
}

/* ── Validar campos básicos ── */
$cardName   = clean_str($_POST['card_name']   ?? '');
$cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
$expiry     = clean_str($_POST['card_expiry'] ?? '');
$cvv        = preg_replace('/\D/', '', $_POST['card_cvv']    ?? '');
$saveCard   = isset($_POST['guardar_tarjeta']) && $_POST['guardar_tarjeta'] === '1';

$errors = [];

if (strlen($cardName) < 3) {
    $errors[] = 'El nombre del titular es requerido.';
}

if ($usingSavedCard) {
    /* Tarjeta guardada: usar datos del DB, solo validar CVV */
    $expiry     = $savedCardData['expiry'];
    $cardNumber = $savedCardData['ultimos4'];
} else {
    if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
        $errors[] = 'Número de tarjeta inválido.';
    }
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $errors[] = 'Fecha de vencimiento inválida (formato MM/AA).';
    }
}

if (strlen($cvv) < 3 || strlen($cvv) > 4) {
    $errors[] = 'CVV inválido.';
}

/* Verificar expiración */
if (empty($errors) && preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
    [$expM, $expY] = explode('/', $expiry);
    $cardTimestamp = mktime(0, 0, 0, (int)$expM + 1, 1, 2000 + (int)$expY);
    if ($cardTimestamp < time()) {
        $errors[] = $usingSavedCard
            ? 'La tarjeta guardada está vencida. Elimínala y usa una nueva.'
            : 'La tarjeta está vencida.';
    }
}

if (!empty($errors)) {
    $_SESSION['pago_error'] = implode(' ', $errors);
    header('Location: pago_tarjeta.php');
    exit;
}

/* ── Obtener total del carrito ── */
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

/* ── Aplicar descuento de puntos (sesión) ── */
$puntosUsados = 0;
if (!empty($_SESSION['puntos_usados'])) {
    /* Verificar que el usuario realmente tenga esos puntos */
    $stmtPV = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
    $stmtPV->bind_param("i", $uid);
    $stmtPV->execute();
    $puntosDisp = (int)($stmtPV->get_result()->fetch_assoc()['puntos'] ?? 0);
    $stmtPV->close();

    $puntosUsados = min((int)$_SESSION['puntos_usados'], $puntosDisp, (int)$totalBruto);
}
$total = max(0.0, $totalBruto - $puntosUsados);

/* ── SANDBOX: siempre aprobar ── */
$approved = true;

if (!$approved) {
    $_SESSION['pago_error'] = 'Pago rechazado por el banco emisor.';
    header('Location: pago_tarjeta.php');
    exit;
}

/* ── Guardar tarjeta tokenizada (solo si es nueva y el usuario lo pidió) ── */
if ($saveCard && !$usingSavedCard) {
    $ultimos4  = substr($cardNumber, -4);
    $token     = bin2hex(random_bytes(32));

    $marca = 'Desconocida';
    if (preg_match('/^4/', $cardNumber))          $marca = 'Visa';
    elseif (preg_match('/^5[1-5]/', $cardNumber)) $marca = 'Mastercard';
    elseif (preg_match('/^3[47]/', $cardNumber))  $marca = 'Amex';
    elseif (preg_match('/^6/', $cardNumber))      $marca = 'Discover';

    $stmtDup = $conn->prepare(
        "SELECT id FROM tarjetas_guardadas WHERE id_usuario = ? AND ultimos4 = ? AND expiry = ?"
    );
    $stmtDup->bind_param("iss", $uid, $ultimos4, $expiry);
    $stmtDup->execute();
    $stmtDup->store_result();

    if ($stmtDup->num_rows === 0) {
        $stmtDup->close();
        $stmtT = $conn->prepare(
            "INSERT INTO tarjetas_guardadas (id_usuario, token, ultimos4, marca, expiry) VALUES (?,?,?,?,?)"
        );
        $stmtT->bind_param("issss", $uid, $token, $ultimos4, $marca, $expiry);
        $stmtT->execute();
        $stmtT->close();
    } else {
        $stmtDup->close();
    }
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

/* ── Registrar pedido ── */
$stmtP = $conn->prepare("INSERT INTO pedidos (id_usuario, total, estado) VALUES (?, ?, 'pagado')");
$stmtP->bind_param("id", $uid, $total);
$stmtP->execute();
$idPedido = $conn->insert_id;
$stmtP->close();

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

/* ── Cashback 10% con reset mensual ── */
$cashback   = (int)floor($total * 0.10);
$mesCurrent = date('Y-m');

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

/* ── Notificación ── */
try {
    $msg   = "Pago con tarjeta de $" . number_format($total, 2) . " procesado. Ganaste $cashback puntos.";
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msg);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Deducir puntos usados de la cuenta del usuario ── */
if ($puntosUsados > 0) {
    $stmtDP = $conn->prepare("UPDATE usuarios SET puntos = GREATEST(0, puntos - ?) WHERE id_usuario = ?");
    $stmtDP->bind_param("ii", $puntosUsados, $uid);
    $stmtDP->execute();
    $stmtDP->close();
    unset($_SESSION['puntos_usados']);
}

/* ── Limpiar carrito ── */
$conn->query("DELETE FROM carrito WHERE id_usuario = $uid");

/* ── Enviar correo de confirmación ── */
try {
    $stmtMail = $conn->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
    $stmtMail->bind_param("i", $uid);
    $stmtMail->execute();
    $userMail = $stmtMail->get_result()->fetch_assoc();
    $stmtMail->close();

    if (!empty($userMail['email'])) {
        $mailBody  = "Hola {$userMail['nombre']},\n\n";
        $mailBody .= "Tus codigos se han generado con exito. Disfrutalos!\n\n";
        foreach ($codigosCompra as $item) {
            $mailBody .= $item['nombre'] . ': ' . $item['codigo'] . "\n";
        }
        $mailBody .= "\n-- Novaplay.com.mx";

        nova_send_mail($userMail['email'], "[{$userMail['nombre']} | {$userMail['email']}] Compra realizada con exito!", $mailBody);
    }
} catch (Exception $e) {
    error_log('[Novaplay] Error al enviar correo de compra (tarjeta): ' . $e->getMessage());
}

$_SESSION['pago_exitoso']   = true;
$_SESSION['pago_cashback']  = $cashback;
$_SESSION['pago_pedido_id'] = $idPedido;

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
