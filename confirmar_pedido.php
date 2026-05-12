<?php
/**
 * NOVAPLAY — CONFIRMAR_PEDIDO.PHP
 * Endpoint AJAX: marca el pedido como confirmado (confirmado=1),
 * devuelve los códigos de activación con plataforma y envía correo con los códigos.
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

if (!$pedidoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

/* Verificar que el pedido pertenece al usuario, está pagado y no confirmado */
$stmt = $conn->prepare(
    "SELECT id_pedido FROM pedidos
     WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pagado' AND confirmado = 0"
);
$stmt->bind_param("ii", $pedidoId, $uid);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no elegible para confirmación']);
    exit;
}

/* Marcar como confirmado */
$stmtUpd = $conn->prepare("UPDATE pedidos SET confirmado = 1 WHERE id_pedido = ?");
$stmtUpd->bind_param("i", $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

/* ── Acreditar cashback (10%) — se otorga aquí, al confirmar, no al pagar ── */
$stmtTotal = $conn->prepare("SELECT total FROM pedidos WHERE id_pedido = ?");
$stmtTotal->bind_param("i", $pedidoId);
$stmtTotal->execute();
$pedidoTotal = (float)($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0);
$stmtTotal->close();

$cashback   = (int)floor($pedidoTotal * 0.10);
$mesCurrent = date('Y-m');

$stmtMesU = $conn->prepare("SELECT puntos_reset_mes FROM usuarios WHERE id_usuario = ?");
$stmtMesU->bind_param("i", $uid);
$stmtMesU->execute();
$mesBD = $stmtMesU->get_result()->fetch_assoc()['puntos_reset_mes'] ?? '';
$stmtMesU->close();

if ($mesBD !== $mesCurrent) {
    $stmtPts = $conn->prepare(
        "UPDATE usuarios SET puntos = ?, puntos_reset_mes = ? WHERE id_usuario = ?"
    );
    $stmtPts->bind_param("isi", $cashback, $mesCurrent, $uid);
} else {
    $stmtPts = $conn->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id_usuario = ?");
    $stmtPts->bind_param("ii", $cashback, $uid);
}
$stmtPts->execute();
$stmtPts->close();

/* Leer puntos actualizados para devolver al cliente */
$stmtNP = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
$stmtNP->bind_param("i", $uid);
$stmtNP->execute();
$nuevosPuntos = (int)($stmtNP->get_result()->fetch_assoc()['puntos'] ?? 0);
$stmtNP->close();

/* Obtener códigos con plataformas desde producto_plataforma */
$stmtCod = $conn->prepare(
    "SELECT ca.nombre_producto, ca.imagen_producto, ca.codigo,
            COALESCE(GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ','), '') AS plataformas
     FROM codigos_activacion ca
     LEFT JOIN producto_plataforma pp ON ca.id_producto = pp.id_producto
     WHERE ca.id_pedido = ?
     GROUP BY ca.id, ca.nombre_producto, ca.imagen_producto, ca.codigo
     ORDER BY ca.id"
);
$stmtCod->bind_param("i", $pedidoId);
$stmtCod->execute();
$res       = $stmtCod->get_result();
$productos = [];
while ($row = $res->fetch_assoc()) {
    $plats      = $row['plataformas'] !== ''
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

/* Enviar correo con los códigos */
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
    . "¡Has confirmado tus productos del pedido #{$pedidoId}!\n"
    . "Aquí están tus códigos de activación:\n\n"
    . "────────────────────────────────────\n"
    . $listaEmail
    . "────────────────────────────────────\n\n"
    . "Guárdalos en un lugar seguro.\n"
    . "También los encontrarás en el historial de tu perfil.\n\n"
    . "— Equipo Novaplay";

try {
    nova_send_mail(
        $userData['email'],
        "Tus códigos de activación — Pedido #{$pedidoId}",
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
]);
