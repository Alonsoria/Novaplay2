<?php
/**
 * NOVAPLAY — PAGAR_CON_PUNTOS.PHP
 * Procesa el pago completo con puntos acumulados.
 * Requiere que el balance cubra el 100% del total del carrito.
 * Sin cashback (los puntos son el pago, no generan más puntos).
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

/* ── Total del carrito ── */
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

/* ── Verificar puntos disponibles ── */
$stmtPts = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtPts->bind_param("i", $uid);
$stmtPts->execute();
$puntos = (int)($stmtPts->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtPts->close();

$puntosNecesarios = (int)ceil($total);

if ($puntos < $puntosNecesarios) {
    $_SESSION['pago_error'] = 'Puntos insuficientes para cubrir el total del carrito.';
    header('Location: carrito.php');
    exit;
}

/* ── Leer ítems del carrito ANTES de limpiar ── */
$cartItems = [];
$resItems  = $conn->query(
    "SELECT c.cantidad, p.id_producto, p.nombre, p.imagen
     FROM carrito c JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
if ($resItems) {
    while ($row = $resItems->fetch_assoc()) $cartItems[] = $row;
}

/* ── Deducir puntos ── */
$stmtDP = $conn->prepare(
    "UPDATE usuarios SET puntos = GREATEST(0, puntos - ?) WHERE id_usuario = ?"
);
$stmtDP->bind_param("ii", $puntosNecesarios, $uid);
$stmtDP->execute();
$stmtDP->close();

/* ── Registrar pedido ── */
$stmtP = $conn->prepare(
    "INSERT INTO pedidos (id_usuario, total, estado, metodo_pago) VALUES (?, ?, 'pagado', 'puntos')"
);
$stmtP->bind_param("id", $uid, $total);
$stmtP->execute();
$idPedido = $conn->insert_id;
$stmtP->close();

/* ── Generar códigos de activación ── */
foreach ($cartItems as $item) {
    $qty = (int)$item['cantidad'];
    for ($i = 0; $i < $qty; $i++) {
        $codigo   = _np_gen_code($conn);
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
    }
}

/* ── Notificación ── */
try {
    $msg   = "Pago de $" . number_format($total, 2) . " con {$puntosNecesarios} puntos procesado correctamente.";
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msg);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Limpiar carrito y puntos en sesión ── */
$conn->query("DELETE FROM carrito WHERE id_usuario = $uid");
unset($_SESSION['puntos_usados']);

/* ── Correo de confirmación ── */
try {
    $stmtMail = $conn->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
    $stmtMail->bind_param("i", $uid);
    $stmtMail->execute();
    $userMail = $stmtMail->get_result()->fetch_assoc();
    $stmtMail->close();

    if (!empty($userMail['email'])) {
        $mailBody  = "Hola {$userMail['nombre']},\n\n";
        $mailBody .= "Pagaste $" . number_format($total, 2) . " MXN con {$puntosNecesarios} puntos. ¡Gracias!\n\n";
        $mailBody .= "Confirma tus productos en tu perfil para desbloquear los códigos.\n\n";
        $mailBody .= "— Equipo Novaplay";
        nova_send_mail(
            $userMail['email'],
            "Pago con puntos procesado — Pedido #{$idPedido}",
            $mailBody
        );
    }
} catch (Exception $e) {
    error_log('[Novaplay] Error correo pago puntos: ' . $e->getMessage());
}

$_SESSION['pago_exitoso']   = true;
$_SESSION['pago_cashback']  = 0;   /* Sin cashback en pagos con puntos */
$_SESSION['pago_pedido_id'] = $idPedido;

header('Location: success.php');
exit;

/* ── Helper: código alfanumérico único de 16 chars ── */
function _np_gen_code(mysqli $conn): string {
    static $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $code = '';
        for ($i = 0; $i < 16; $i++) $code .= $chars[random_int(0, 35)];
        $chk = $conn->prepare("SELECT id FROM codigos_activacion WHERE codigo = ? LIMIT 1");
        $chk->bind_param("s", $code);
        $chk->execute();
        $chk->store_result();
        $exists = $chk->num_rows > 0;
        $chk->close();
        if (!$exists) return $code;
    }
    return strtoupper(bin2hex(random_bytes(8)));
}
