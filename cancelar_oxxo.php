<?php
/**
 * NOVAPLAY — CANCELAR_OXXO.PHP
 * Endpoint AJAX: cancela un pedido OXXO cuyo plazo ha vencido.
 * Llamado por el cliente cuando el countdown llega a 0.
 */

require_once 'security.php';
require_once 'config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false]);
    exit;
}

$uid      = (int)$_SESSION['user_id'];
$pedidoId = clean_int($_POST['pedido_id'] ?? null);

if (!$pedidoId) {
    echo json_encode(['success' => false]);
    exit;
}

/* Verificar que el pedido pertenece al usuario y que efectivamente venció */
$stmt = $conn->prepare(
    "SELECT id_pedido, oxxo_expira FROM pedidos
     WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pendiente_oxxo'"
);
$stmt->bind_param("ii", $pedidoId, $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || strtotime($row['oxxo_expira']) >= time()) {
    /* No existe, ya fue cancelado/pagado, o aún no venció */
    echo json_encode(['success' => false]);
    exit;
}

$stmtUpd = $conn->prepare(
    "UPDATE pedidos SET estado = 'cancelado'
     WHERE id_pedido = ? AND estado = 'pendiente_oxxo'"
);
$stmtUpd->bind_param("i", $pedidoId);
$stmtUpd->execute();
$stmtUpd->close();

echo json_encode(['success' => true]);
