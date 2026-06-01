<?php
/**
 * NOVAPLAY — CONFIRMAR_PEDIDO.PHP  v2
 * Confirmación granular por ítem.
 *
 * POST: pedido_id  — obligatorio
 *       codigo_ids — JSON array de IDs de codigos_activacion (opcional).
 *                    Si se omite, confirma TODOS los ítems pendientes del pedido.
 *
 * Responde JSON:
 *   { success, productos[], cashback, puntos, all_done, remaining }
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

if (!$pedidoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

/* Verificar que el pedido pertenece al usuario y está pagado */
$stmt = $conn->prepare(
    "SELECT id_pedido FROM pedidos
     WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pagado'"
);
$stmt->bind_param("ii", $pedidoId, $uid);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado o no elegible para confirmación']);
    exit;
}

/* ── Resolver qué IDs confirmar ── */
$codigoIds = [];
if ($rawIds) {
    $decoded = json_decode($rawIds, true);
    if (is_array($decoded)) {
        $codigoIds = array_values(
            array_filter(array_map('intval', $decoded), fn($id) => $id > 0)
        );
    }
}

if (empty($codigoIds)) {
    /* Sin selección → confirmar TODOS los ítems pendientes */
    $stmtAll = $conn->prepare(
        "SELECT id FROM codigos_activacion
         WHERE id_pedido = ? AND devuelto = 0 AND confirmado = 0"
    );
    $stmtAll->bind_param("i", $pedidoId);
    $stmtAll->execute();
    $resAll = $stmtAll->get_result();
    while ($row = $resAll->fetch_assoc()) $codigoIds[] = (int)$row['id'];
    $stmtAll->close();
}

if (empty($codigoIds)) {
    echo json_encode(['error' => 'No hay productos pendientes de confirmar en este pedido']);
    exit;
}

/* ── Validar que los IDs pertenecen al pedido y son elegibles ── */
$ph      = implode(',', array_fill(0, count($codigoIds), '?'));
$typesV  = 'i' . str_repeat('i', count($codigoIds));
$paramsV = array_merge([$pedidoId], $codigoIds);

$stmtV = $conn->prepare(
    "SELECT ca.id, COALESCE(p.precio, 0) AS precio
     FROM codigos_activacion ca
     LEFT JOIN productos p ON ca.id_producto = p.id_producto
     WHERE ca.id_pedido = ? AND ca.id IN ($ph)
       AND ca.devuelto = 0 AND ca.confirmado = 0"
);
$stmtV->bind_param($typesV, ...$paramsV);
$stmtV->execute();
$resV      = $stmtV->get_result();
$validIds  = [];
$sumPrecios = 0.0;
while ($row = $resV->fetch_assoc()) {
    $validIds[]  = (int)$row['id'];
    $sumPrecios += (float)$row['precio'];
}
$stmtV->close();

if (empty($validIds)) {
    echo json_encode(['error' => 'Ningún producto seleccionado es elegible para confirmación']);
    exit;
}

/* ── Marcar como confirmados ── */
$ph2     = implode(',', array_fill(0, count($validIds), '?'));
$typesU  = str_repeat('i', count($validIds));
$stmtUpd = $conn->prepare("UPDATE codigos_activacion SET confirmado = 1 WHERE id IN ($ph2)");
$stmtUpd->bind_param($typesU, ...$validIds);
$stmtUpd->execute();
$stmtUpd->close();

/* ── ¿Quedan ítems pendientes? ── */
$stmtChk = $conn->prepare(
    "SELECT COUNT(*) AS c FROM codigos_activacion
     WHERE id_pedido = ? AND devuelto = 0 AND confirmado = 0"
);
$stmtChk->bind_param("i", $pedidoId);
$stmtChk->execute();
$remaining = (int)($stmtChk->get_result()->fetch_assoc()['c'] ?? 0);
$stmtChk->close();

/* Cerrar pedido si ya no hay pendientes */
if ($remaining === 0) {
    $stmtPed = $conn->prepare("UPDATE pedidos SET confirmado = 1 WHERE id_pedido = ?");
    $stmtPed->bind_param("i", $pedidoId);
    $stmtPed->execute();
    $stmtPed->close();
}

/* ── Cashback sobre los ítems confirmados en esta llamada ── */
$cashback   = (int)floor($sumPrecios * 0.10);
$mesCurrent = date('Y-m');

if ($cashback > 0) {
    $stmtMes = $conn->prepare("SELECT puntos_reset_mes FROM usuarios WHERE id_usuario = ?");
    $stmtMes->bind_param("i", $uid);
    $stmtMes->execute();
    $mesBD = $stmtMes->get_result()->fetch_assoc()['puntos_reset_mes'] ?? '';
    $stmtMes->close();

    if ($mesBD !== $mesCurrent) {
        $stmtPts = $conn->prepare(
            "UPDATE usuarios SET puntos = puntos + ?, puntos_reset_mes = ? WHERE id_usuario = ?"
        );
        $stmtPts->bind_param("isi", $cashback, $mesCurrent, $uid);
    } else {
        $stmtPts = $conn->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id_usuario = ?");
        $stmtPts->bind_param("ii", $cashback, $uid);
    }
    $stmtPts->execute();
    $stmtPts->close();
}

$stmtNP = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtNP->bind_param("i", $uid);
$stmtNP->execute();
$nuevosPuntos = (int)($stmtNP->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtNP->close();

/* ── Obtener códigos de los ítems recién confirmados ── */
$phC     = implode(',', array_fill(0, count($validIds), '?'));
$typesC  = str_repeat('i', count($validIds));
$stmtCod = $conn->prepare(
    "SELECT ca.nombre_producto, ca.imagen_producto, ca.codigo,
            COALESCE(GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ','), '') AS plataformas
     FROM codigos_activacion ca
     LEFT JOIN producto_plataforma pp ON ca.id_producto = pp.id_producto
     WHERE ca.id IN ($phC)
     GROUP BY ca.id, ca.nombre_producto, ca.imagen_producto, ca.codigo
     ORDER BY ca.id"
);
$stmtCod->bind_param($typesC, ...$validIds);
$stmtCod->execute();
$res      = $stmtCod->get_result();
$productos = [];
while ($row = $res->fetch_assoc()) {
    $plats     = $row['plataformas'] !== ''
        ? array_map('intval', explode(',', $row['plataformas']))
        : [];
    $productos[] = [
        'nombre_producto' => $row['nombre_producto'],
        'imagen_producto' => $row['imagen_producto'],
        'plataformas'     => $plats,
        'codigo'          => $row['codigo'],
    ];
}
$stmtCod->close();

/* ── Correo con los códigos confirmados ── */
$stmtU = $conn->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$platNombres = [1 => 'Xbox', 2 => 'PlayStation', 3 => 'Steam', 4 => 'Nintendo'];
$listaEmail  = '';
foreach ($productos as $p) {
    $codFmt    = implode('-', str_split($p['codigo'], 4));
    $platLinea = '';
    if (!empty($p['plataformas'])) {
        $names     = array_map(fn($id) => $platNombres[$id] ?? "Plataforma $id", $p['plataformas']);
        $platLinea = ' (' . implode(', ', $names) . ')';
    }
    $listaEmail .= "  {$p['nombre_producto']}{$platLinea}: {$codFmt}\n";
}

$emailBody = "Hola {$userData['nombre']},\n\n"
    . "¡Confirmaste " . count($productos) . " producto(s) del pedido #{$pedidoId}!\n\n"
    . "────────────────────────────────────\n"
    . $listaEmail
    . "────────────────────────────────────\n\n"
    . ($remaining > 0
        ? "Tienes {$remaining} producto(s) pendientes de confirmar en este pedido.\n\n"
        : '')
    . "Guárdalos en un lugar seguro.\n"
    . "— Equipo Novaplay";

try {
    nova_send_mail(
        $userData['email'],
        "Tus códigos — Pedido #{$pedidoId}",
        $emailBody
    );
} catch (Exception $e) {
    error_log('[Novaplay] Error al enviar correo de confirmación: ' . $e->getMessage());
}

echo json_encode([
    'success'   => true,
    'productos' => $productos,
    'cashback'  => $cashback,
    'puntos'    => $nuevosPuntos,
    'all_done'  => ($remaining === 0),
    'remaining' => $remaining,
]);
