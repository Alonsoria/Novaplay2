<?php
/**
 * NOVAPLAY — SOLICITAR_DEVOLUCION.PHP  v2
 * Endpoint AJAX: devolución granular por ítem.
 *
 * Cambios v2:
 *  - Sin restricción de "una sola solicitud por pedido"; se permiten múltiples
 *    mientras existan ítems con devuelto=0 Y confirmado=0.
 *  - estado del pedido: 'reembolsado' solo cuando TODOS los ítems son devueltos
 *    y ninguno fue confirmado previamente. En caso contrario, el pedido permanece
 *    'pagado' con confirmado=1 (todo accounted for).
 *  - Los puntos se acreditan al confirmar, nunca al devolver.
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
$rawIds   = $_POST['codigo_ids'] ?? '';

if (!$pedidoId || !$rawIds) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$codigoIds = json_decode($rawIds, true);
if (!is_array($codigoIds) || empty($codigoIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Selecciona al menos un producto']);
    exit;
}

$codigoIds = array_values(array_filter(array_map('intval', $codigoIds), fn($id) => $id > 0));
if (empty($codigoIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'IDs de producto inválidos']);
    exit;
}

/* ── Verificar que el pedido pertenece al usuario y está activo ──
   En v2 ya NO se requiere confirmado=0 a nivel pedido;
   la elegibilidad se evalúa por ítem. */
$stmt = $conn->prepare(
    "SELECT id_pedido, total, fecha, COALESCE(metodo_pago, '') AS metodo_pago FROM pedidos
     WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pagado'"
);
$stmt->bind_param("ii", $pedidoId, $uid);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado o no elegible. Solo pedidos activos (pagados) admiten devolución.']);
    exit;
}

/* ── Validar que los IDs pertenecen al pedido y son elegibles ──
   Elegible = devuelto=0 AND confirmado=0 */
$ph        = implode(',', array_fill(0, count($codigoIds), '?'));
$typesIds  = 'i' . str_repeat('i', count($codigoIds));
$paramsIds = array_merge([$pedidoId], $codigoIds);

$stmtItems = $conn->prepare(
    "SELECT ca.id, ca.nombre_producto, p.precio
     FROM codigos_activacion ca
     JOIN productos p ON ca.id_producto = p.id_producto
     WHERE ca.id_pedido = ? AND ca.id IN ($ph)
       AND ca.devuelto = 0 AND ca.confirmado = 0"
);
$stmtItems->bind_param($typesIds, ...$paramsIds);
$stmtItems->execute();
$resItems      = $stmtItems->get_result();
$itemsDevueltos = [];
$totalDevuelto  = 0.0;
while ($row = $resItems->fetch_assoc()) {
    $itemsDevueltos[] = $row;
    $totalDevuelto   += (float)$row['precio'];
}
$stmtItems->close();

if (empty($itemsDevueltos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Los productos seleccionados no son elegibles (ya confirmados o devueltos).']);
    exit;
}

/* ── Marcar ítems como devueltos ── */
$stmtMark = $conn->prepare(
    "UPDATE codigos_activacion SET devuelto = 1
     WHERE id_pedido = ? AND id IN ($ph) AND devuelto = 0 AND confirmado = 0"
);
$stmtMark->bind_param($typesIds, ...$paramsIds);
$stmtMark->execute();
$stmtMark->close();

/* ── Evaluar estado resultante del pedido ── */
$stmtRem = $conn->prepare(
    "SELECT
       SUM(CASE WHEN devuelto=0 AND confirmado=0 THEN 1 ELSE 0 END) AS pendientes,
       SUM(CASE WHEN confirmado=1               THEN 1 ELSE 0 END) AS confirmados
     FROM codigos_activacion WHERE id_pedido = ?"
);
$stmtRem->bind_param("i", $pedidoId);
$stmtRem->execute();
$rem        = $stmtRem->get_result()->fetch_assoc();
$stmtRem->close();
$pendientes  = (int)$rem['pendientes'];
$confirmados = (int)$rem['confirmados'];

/* monto_devuelto acumulado */
$stmtUpd = $conn->prepare(
    "UPDATE pedidos SET monto_devuelto = monto_devuelto + ? WHERE id_pedido = ?"
);
$stmtUpd->bind_param("di", $totalDevuelto, $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

if ($pendientes === 0) {
    if ($confirmados === 0) {
        /* Devolución total: ningún ítem fue confirmado → reembolsado completo */
        $stmtClose = $conn->prepare(
            "UPDATE pedidos SET estado = 'reembolsado', confirmado = 1 WHERE id_pedido = ?"
        );
    } else {
        /* Mix: algunos confirmados, resto devueltos → pedido cerrado, estado pagado */
        $stmtClose = $conn->prepare(
            "UPDATE pedidos SET confirmado = 1 WHERE id_pedido = ?"
        );
    }
    $stmtClose->bind_param("i", $pedidoId);
    $stmtClose->execute();
    $stmtClose->close();
}

/* ── Construir resumen de nombres ── */
$nombreCounts = [];
foreach ($itemsDevueltos as $item) {
    $n = $item['nombre_producto'];
    $nombreCounts[$n] = ($nombreCounts[$n] ?? 0) + 1;
}
$productosDisplay = [];
foreach ($nombreCounts as $nombre => $cantidad) {
    $productosDisplay[] = $cantidad > 1 ? "{$nombre} × {$cantidad}" : $nombre;
}
$listaProductos = implode("\n  - ", $productosDisplay);

/* ── Registrar la solicitud ── */
$productosJson = json_encode($productosDisplay, JSON_UNESCAPED_UNICODE);
$stmtIns = $conn->prepare(
    "INSERT INTO solicitudes_devolucion (id_pedido, id_usuario, productos, estado)
     VALUES (?, ?, ?, 'aprobado')"
);
$stmtIns->bind_param("iis", $pedidoId, $uid, $productosJson);
$stmtIns->execute();
$stmtIns->close();

/* ── Devolución OXXO / Puntos: reembolso como puntos en lugar de tarjeta ── */
$esOxxo           = ($pedido['metodo_pago'] === 'oxxo');
$esPuntos         = ($pedido['metodo_pago'] === 'puntos');
$puntosAcreditados = 0;

if ($esOxxo || $esPuntos) {
    $puntosAcreditados = (int)round($totalDevuelto);
    if ($puntosAcreditados > 0) {
        $stmtPtsAdd = $conn->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id_usuario = ?");
        $stmtPtsAdd->bind_param("ii", $puntosAcreditados, $uid);
        $stmtPtsAdd->execute();
        $stmtPtsAdd->close();
    }
}

/* ── Notificación en plataforma ── */
if ($esOxxo || $esPuntos) {
    $msgNot = $pendientes > 0
        ? "Devolución parcial del pedido #{$pedidoId} aprobada. Se acreditaron {$puntosAcreditados} puntos a tu cuenta."
        : "Devolución del pedido #{$pedidoId} aprobada. Se acreditaron {$puntosAcreditados} puntos de Novaplay.";
} else {
    $msgNot = $pendientes > 0
        ? "Devolución parcial del pedido #{$pedidoId} aprobada. Tienes {$pendientes} ítem(s) pendientes."
        : "Devolución del pedido #{$pedidoId} aprobada. Reembolso en camino.";
}

try {
    $stmtN = $conn->prepare("INSERT INTO notificaciones (id_usuario, mensaje) VALUES (?, ?)");
    $stmtN->bind_param("is", $uid, $msgNot);
    $stmtN->execute();
    $stmtN->close();
} catch (Exception $e) {}

/* ── Correo de confirmación ── */
$stmtU = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$fechaPedido = date('d/m/Y', strtotime($pedido['fecha']));

if ($esOxxo || $esPuntos) {
    $tipoPago  = $esOxxo ? 'OXXO PAY' : 'PUNTOS NOVAPLAY';
    $emailBody = "Hola {$userData['nombre']},\n\n"
        . "¡Tu solicitud de devolución ha sido APROBADA!\n\n"
        . "────────────────────────────────────\n"
        . "DETALLE DE LA DEVOLUCIÓN\n"
        . "────────────────────────────────────\n"
        . "Número de pedido:  #{$pedidoId}\n"
        . "Fecha del pedido:  {$fechaPedido}\n\n"
        . "Productos devueltos:\n  - {$listaProductos}\n"
        . "\n────────────────────────────────────\n"
        . "INFORMACIÓN DEL REEMBOLSO ({$tipoPago})\n"
        . "────────────────────────────────────\n"
        . "Como el pago original fue realizado con {$tipoPago}, el reembolso\n"
        . "se ha acreditado como puntos de Novaplay:\n\n"
        . "  Puntos acreditados: {$puntosAcreditados} pts (equivalentes a \${$puntosAcreditados}.00 MXN)\n\n"
        . "Puedes usar tus puntos como descuento en tu próxima compra.\n\n"
        . ($pendientes > 0
            ? "Tienes {$pendientes} producto(s) pendientes de confirmar en este pedido.\n\n"
            : '')
        . "Gracias por confiar en Novaplay.\n\n"
        . "— Equipo Novaplay";
} else {
    $emailBody = "Hola {$userData['nombre']},\n\n"
        . "¡Tu solicitud de devolución ha sido APROBADA!\n\n"
        . "────────────────────────────────────\n"
        . "DETALLE DE LA DEVOLUCIÓN\n"
        . "────────────────────────────────────\n"
        . "Número de pedido:  #{$pedidoId}\n"
        . "Fecha del pedido:  {$fechaPedido}\n\n"
        . "Productos devueltos:\n  - {$listaProductos}\n"
        . "\n────────────────────────────────────\n"
        . "INFORMACIÓN DEL REEMBOLSO\n"
        . "────────────────────────────────────\n"
        . "El importe de los productos devueltos será reembolsado\n"
        . "a tu método de pago original en 1–3 días hábiles.\n\n"
        . ($pendientes > 0
            ? "Tienes {$pendientes} producto(s) pendientes de confirmar en este pedido.\n\n"
            : '')
        . "Gracias por confiar en Novaplay.\n\n"
        . "— Equipo Novaplay";
}

nova_send_mail(
    $userData['email'],
    "Devolución aprobada — Pedido #{$pedidoId}",
    $emailBody
);

/* ── Leer puntos actualizados ── */
$stmtPts2 = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtPts2->bind_param("i", $uid);
$stmtPts2->execute();
$nuevosPuntos = (int)($stmtPts2->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtPts2->close();

/* ── Leer total efectivo actualizado ── */
$stmtTot = $conn->prepare("SELECT total, COALESCE(monto_devuelto,0) AS monto_devuelto FROM pedidos WHERE id_pedido = ?");
$stmtTot->bind_param("i", $pedidoId);
$stmtTot->execute();
$pedidoActual = $stmtTot->get_result()->fetch_assoc();
$stmtTot->close();
$totalEfectivo = max(0.0, (float)$pedidoActual['total'] - (float)$pedidoActual['monto_devuelto']);

echo json_encode([
    'success'           => true,
    'puntos'            => $nuevosPuntos,
    'oxxo_devolucion'   => $esOxxo,
    'puntos_acreditados'=> $puntosAcreditados,
    'pendientes'        => $pendientes,
    'confirmados'    => $confirmados,
    'total_efectivo' => $totalEfectivo,
]);
