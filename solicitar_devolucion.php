<?php
/**
 * NOVAPLAY — SOLICITAR_DEVOLUCION.PHP
 * Endpoint AJAX: registra y aprueba inmediatamente una solicitud de devolución.
 *
 * Recibe `codigo_ids` (array de IDs de codigos_activacion), lo que permite
 * devolver N copias de un producto sin afectar las copias restantes.
 *
 * Comportamiento según el alcance de la devolución:
 *   - Devolución TOTAL:     estado → 'reembolsado', confirmado permanece 0.
 *                           No se acreditan puntos (nunca se emitieron).
 *   - Devolución PARCIAL:   estado → 'reembolsado', confirmado → 1 (auto-confirma
 *                           los ítems restantes para que los códigos sean visibles).
 *                           Se acreditan puntos sobre (total_pagado − total_devuelto).
 *                           La respuesta incluye `parcial:true` y los códigos del pedido.
 *
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

/* Sanitizar: solo enteros positivos */
$codigoIds = array_values(array_filter(array_map('intval', $codigoIds), fn($id) => $id > 0));
if (empty($codigoIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'IDs de producto inválidos']);
    exit;
}

/* Verificar que el pedido pertenece al usuario y está pagado sin confirmar */
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

/* ── Validar que los IDs pertenecen al pedido y obtener datos ── */
$ph          = implode(',', array_fill(0, count($codigoIds), '?'));
$typesIds    = 'i' . str_repeat('i', count($codigoIds));
$paramsIds   = array_merge([$pedidoId], $codigoIds);

$stmtItems = $conn->prepare(
    "SELECT ca.id, ca.nombre_producto, p.precio
     FROM codigos_activacion ca
     JOIN productos p ON ca.id_producto = p.id_producto
     WHERE ca.id_pedido = ? AND ca.id IN ($ph)"
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

/* Marcar los códigos seleccionados como devueltos */
$stmtMark = $conn->prepare(
    "UPDATE codigos_activacion SET devuelto = 1 WHERE id_pedido = ? AND id IN ($ph)"
);
$stmtMark->bind_param($typesIds, ...$paramsIds);
$stmtMark->execute();
$stmtMark->close();

if (empty($itemsDevueltos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Los productos seleccionados no pertenecen a este pedido.']);
    exit;
}

/* ── Detectar si es devolución parcial o total ── */
$stmtCountAll = $conn->prepare(
    "SELECT COUNT(*) AS c FROM codigos_activacion WHERE id_pedido = ?"
);
$stmtCountAll->bind_param("i", $pedidoId);
$stmtCountAll->execute();
$totalItems = (int)($stmtCountAll->get_result()->fetch_assoc()['c'] ?? 0);
$stmtCountAll->close();

$esDevolucionParcial = (count($itemsDevueltos) < $totalItems);

/* ── Construir resumen de nombres para email y registro ── */
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

/* ── Registrar la solicitud como aprobada ── */
$productosJson = json_encode($productosDisplay, JSON_UNESCAPED_UNICODE);
$stmtIns = $conn->prepare(
    "INSERT INTO solicitudes_devolucion (id_pedido, id_usuario, productos, estado) VALUES (?, ?, ?, 'aprobado')"
);
$stmtIns->bind_param("iis", $pedidoId, $uid, $productosJson);
$stmtIns->execute();
$stmtIns->close();

/* ── Actualizar el pedido ── */
if ($esDevolucionParcial) {
    /* Parcial: se auto-confirma para que los códigos restantes sean visibles. */
    $stmtUpd = $conn->prepare(
        "UPDATE pedidos SET estado = 'reembolsado', confirmado = 1,
         monto_devuelto = monto_devuelto + ? WHERE id_pedido = ?"
    );
} else {
    /* Total: solo se marca como reembolsado; confirmado permanece 0. */
    $stmtUpd = $conn->prepare(
        "UPDATE pedidos SET estado = 'reembolsado',
         monto_devuelto = monto_devuelto + ? WHERE id_pedido = ?"
    );
}
$stmtUpd->bind_param("di", $totalDevuelto, $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

/* ── Puntos: solo se acreditan en devolución PARCIAL (sobre el importe restante) ── */
$puntosGanados = 0;
if ($esDevolucionParcial) {
    $totalPagado   = (float)$pedido['total'];
    $totalRestante = max(0.0, $totalPagado - $totalDevuelto);
    $puntosGanados = (int)floor($totalRestante * 0.10);

    if ($puntosGanados > 0) {
        $mesCurrent = date('Y-m');
        $stmtMes = $conn->prepare("SELECT puntos_reset_mes FROM usuarios WHERE id_usuario = ?");
        $stmtMes->bind_param("i", $uid);
        $stmtMes->execute();
        $mesBD = $stmtMes->get_result()->fetch_assoc()['puntos_reset_mes'] ?? '';
        $stmtMes->close();

        if ($mesBD !== $mesCurrent) {
            $stmtPts = $conn->prepare(
                "UPDATE usuarios SET puntos = ?, puntos_reset_mes = ? WHERE id_usuario = ?"
            );
            $stmtPts->bind_param("isi", $puntosGanados, $mesCurrent, $uid);
        } else {
            $stmtPts = $conn->prepare(
                "UPDATE usuarios SET puntos = puntos + ? WHERE id_usuario = ?"
            );
            $stmtPts->bind_param("ii", $puntosGanados, $uid);
        }
        $stmtPts->execute();
        $stmtPts->close();
    }
}

/* ── Notificación en plataforma ── */
if ($esDevolucionParcial) {
    $msgNot = "Devolución parcial del pedido #{$pedidoId} aprobada."
        . ($puntosGanados > 0 ? " Ganaste {$puntosGanados} pts por los productos confirmados." : '');
} else {
    $msgNot = "Tu devolución del pedido #{$pedidoId} fue aprobada. Reembolso en camino.";
}
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

/* ── Correo de confirmación ── */
$fechaPedido = date('d/m/Y', strtotime($pedido['fecha']));
$ptsLinea    = ($esDevolucionParcial && $puntosGanados > 0)
    ? "\nPuntos acreditados por productos confirmados: {$puntosGanados} pts\n"
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
    . "Tiempo estimado: 1 a 3 días hábiles a partir de hoy.\n\n"
    . ($esDevolucionParcial
        ? "Los códigos de los productos que conservas ya están disponibles\nen el historial de tu perfil.\n\n"
        : '')
    . "Gracias por confiar en Novaplay.\n\n"
    . "— Equipo Novaplay";

nova_send_mail(
    $userData['email'],
    "Devolución aprobada — Reembolso en camino — Pedido #{$pedidoId}",
    $emailBody
);

/* ── Leer puntos actualizados ── */
$stmtPts2 = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtPts2->bind_param("i", $uid);
$stmtPts2->execute();
$nuevosPuntos = (int)($stmtPts2->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtPts2->close();

/* ── Para devolución parcial: obtener todos los códigos del pedido para el modal ── */
$productosCodigos = null;
if ($esDevolucionParcial) {
    $stmtCod = $conn->prepare(
        "SELECT ca.nombre_producto, ca.imagen_producto, ca.codigo,
                COALESCE(GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ','), '') AS plataformas
         FROM codigos_activacion ca
         LEFT JOIN producto_plataforma pp ON ca.id_producto = pp.id_producto
         WHERE ca.id_pedido = ? AND ca.devuelto = 0
         GROUP BY ca.id, ca.nombre_producto, ca.imagen_producto, ca.codigo
         ORDER BY ca.id"
    );
    $stmtCod->bind_param("i", $pedidoId);
    $stmtCod->execute();
    $resCod = $stmtCod->get_result();
    $productosCodigos = [];
    while ($row = $resCod->fetch_assoc()) {
        $plats = $row['plataformas'] !== ''
            ? array_map('intval', explode(',', $row['plataformas']))
            : [];
        $productosCodigos[] = [
            'nombre_producto' => $row['nombre_producto'],
            'imagen_producto' => $row['imagen_producto'],
            'plataformas'     => $plats,
            'codigo'          => $row['codigo'],
        ];
    }
    $stmtCod->close();
}

echo json_encode([
    'success'        => true,
    'puntos'         => $nuevosPuntos,
    'parcial'        => $esDevolucionParcial,
    'productos'      => $productosCodigos,
    'total_efectivo' => max(0.0, (float)$pedido['total'] - $totalDevuelto),
]);
