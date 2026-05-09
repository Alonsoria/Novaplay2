<?php
/**
 * NOVAPLAY — SOLICITAR_DEVOLUCION.PHP
 * Endpoint AJAX: registra y aprueba inmediatamente una solicitud de devolución,
 * deduce los puntos de cashback proporcionales al precio de los productos devueltos
 * y envía correo de confirmación con información completa del reembolso.
 * Solo acepta POST. Requiere sesión activa.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid      = (int)$_SESSION['user_id'];
$pedidoId = clean_int($_POST['pedido_id'] ?? null);
$rawProds = $_POST['productos'] ?? '';

if (!$pedidoId || !$rawProds) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$productosSel = json_decode($rawProds, true);
if (!is_array($productosSel) || empty($productosSel)) {
    http_response_code(400);
    echo json_encode(['error' => 'Selecciona al menos un producto']);
    exit;
}

/* Sanitizar nombres de productos */
$productosSel = array_values(array_map(fn($p) => clean_str((string)$p), $productosSel));

/* Verificar que el pedido pertenece al usuario y está pagado */
$stmt = $conn->prepare(
    "SELECT id_pedido, total, fecha FROM pedidos
     WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pagado' AND confirmado = 0"
);
$stmt->bind_param("ii", $pedidoId, $uid);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado o no elegible. Los pedidos ya confirmados no admiten devolución.']);
    exit;
}

/* Verificar que no hay ya una solicitud para este pedido */
$stmtChk = $conn->prepare(
    "SELECT id FROM solicitudes_devolucion
     WHERE id_pedido = ? AND id_usuario = ? AND estado IN ('pendiente','aprobado')"
);
$stmtChk->bind_param("ii", $pedidoId, $uid);
$stmtChk->execute();
$stmtChk->store_result();
if ($stmtChk->num_rows > 0) {
    $stmtChk->close();
    echo json_encode(['error' => 'Ya existe una solicitud de devolución registrada para este pedido.']);
    exit;
}
$stmtChk->close();

/* ── Calcular puntos a deducir ── */
/* Suma el precio de cada producto devuelto usando codigos_activacion (tiene id_producto)
   JOIN productos (tiene precio). Si compraron 2 copias del mismo juego y devuelven
   ese nombre, se suman ambas. */
$puntosDeducidos = 0;
$placeholders    = implode(',', array_fill(0, count($productosSel), '?'));
$types           = 'i' . str_repeat('s', count($productosSel));
$params          = array_merge([$pedidoId], $productosSel);

$stmtPrecio = $conn->prepare(
    "SELECT COALESCE(SUM(p.precio), 0) AS total_devuelto
     FROM codigos_activacion ca
     JOIN productos p ON ca.id_producto = p.id_producto
     WHERE ca.id_pedido = ?
       AND ca.nombre_producto IN ($placeholders)"
);
$stmtPrecio->bind_param($types, ...$params);
$stmtPrecio->execute();
$totalDevuelto = (float)($stmtPrecio->get_result()->fetch_assoc()['total_devuelto'] ?? 0);
$stmtPrecio->close();

$puntosDeducidos = (int)floor($totalDevuelto * 0.10);

if ($puntosDeducidos > 0) {
    $stmtDed = $conn->prepare(
        "UPDATE usuarios SET puntos = GREATEST(0, puntos - ?) WHERE id_usuario = ?"
    );
    $stmtDed->bind_param("ii", $puntosDeducidos, $uid);
    $stmtDed->execute();
    $stmtDed->close();
}

/* ── Registrar la solicitud como aprobada directamente ── */
$productosJson = json_encode($productosSel, JSON_UNESCAPED_UNICODE);
$stmtIns = $conn->prepare(
    "INSERT INTO solicitudes_devolucion (id_pedido, id_usuario, productos, estado) VALUES (?, ?, ?, 'aprobado')"
);
$stmtIns->bind_param("iis", $pedidoId, $uid, $productosJson);
$stmtIns->execute();
$stmtIns->close();

/* ── Marcar el pedido como reembolsado ── */
$stmtUpd = $conn->prepare("UPDATE pedidos SET estado = 'reembolsado' WHERE id_pedido = ?");
$stmtUpd->bind_param("i", $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

/* ── Notificación en plataforma ── */
$msgNot = "Tu devolución del pedido #{$pedidoId} fue aprobada."
    . ($puntosDeducidos > 0 ? " Se dedujeron {$puntosDeducidos} pts de cashback." : '');
try {
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msgNot);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Obtener datos del usuario para el correo ── */
$stmtU = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

/* ── Correo unificado de solicitud + aprobación ── */
$fechaPedido    = date('d/m/Y', strtotime($pedido['fecha']));
$listaProductos = implode("\n  - ", $productosSel);
$ptsLinea       = $puntosDeducidos > 0
    ? "\nPuntos de cashback deducidos: {$puntosDeducidos} pts\n"
    : '';

$emailBody = "Hola {$userData['nombre']},\n\n"
    . "¡Tu solicitud de devolución ha sido APROBADA!\n\n"
    . "────────────────────────────────────\n"
    . "DETALLE DE LA DEVOLUCIÓN\n"
    . "────────────────────────────────────\n"
    . "Número de pedido:  #{$pedidoId}\n"
    . "Fecha del pedido:  {$fechaPedido}\n"
    . "Total del pedido:  $" . number_format((float)$pedido['total'], 2) . " MXN\n\n"
    . "Productos devueltos:\n  - {$listaProductos}\n"
    . $ptsLinea
    . "\n────────────────────────────────────\n"
    . "INFORMACIÓN DEL REEMBOLSO\n"
    . "────────────────────────────────────\n"
    . "El importe correspondiente a los productos devueltos será\n"
    . "reembolsado a tu método de pago original.\n\n"
    . "Tiempo estimado: 1 a 3 días hábiles a partir de hoy.\n"
    . "El tiempo exacto puede variar según la entidad bancaria\n"
    . "o procesador de pago que hayas utilizado.\n\n"
    . "Si pasados 3 días hábiles no ves el reembolso reflejado,\n"
    . "contáctanos respondiendo a este correo y con gusto te ayudamos.\n\n"
    . "Gracias por confiar en Novaplay.\n\n"
    . "— Equipo Novaplay";

nova_send_mail(
    $userData['email'],
    "Devolución aprobada — Reembolso en camino — Pedido #{$pedidoId}",
    $emailBody
);

/* ── Leer puntos actualizados para la respuesta ── */
$stmtPts = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtPts->bind_param("i", $uid);
$stmtPts->execute();
$nuevosPuntos = (int)($stmtPts->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtPts->close();

echo json_encode(['success' => true, 'puntos' => $nuevosPuntos]);
