<?php
/**
 * NOVAPLAY — ACTUALIZAR_CARRITO.PHP
 * Endpoint AJAX: aumentar, disminuir o eliminar un item del carrito.
 */

require_once 'security.php';
require_once 'config.php';

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

$uid    = (int)$_SESSION['user_id'];
$itemId = clean_int($_POST['id'] ?? null);
$action = clean_str($_POST['action'] ?? '');

if (!$itemId || !in_array($action, ['increase', 'decrease', 'remove'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit;
}

/* Verificar que el item pertenece al usuario */
$stmt = $conn->prepare("SELECT id, cantidad FROM carrito WHERE id = ? AND id_usuario = ?");
$stmt->bind_param("ii", $itemId, $uid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item no encontrado']);
    exit;
}

$newQty = (int)$item['cantidad'];

if ($action === 'remove' || ($action === 'decrease' && $newQty <= 1)) {
    $stmt2 = $conn->prepare("DELETE FROM carrito WHERE id = ? AND id_usuario = ?");
    $stmt2->bind_param("ii", $itemId, $uid);
    $stmt2->execute();
    $stmt2->close();
    $newQty = 0;
} elseif ($action === 'increase') {
    $newQty++;
    $stmt2 = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
    $stmt2->bind_param("ii", $newQty, $itemId);
    $stmt2->execute();
    $stmt2->close();
} elseif ($action === 'decrease') {
    $newQty--;
    $stmt2 = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
    $stmt2->bind_param("ii", $newQty, $itemId);
    $stmt2->execute();
    $stmt2->close();
}

/* Nuevo conteo total */
$stmtC = $conn->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = ?");
$stmtC->bind_param("i", $uid);
$stmtC->execute();
$cartCount = (int)($stmtC->get_result()->fetch_assoc()['total'] ?? 0);
$stmtC->close();

echo json_encode(['success' => true, 'qty' => $newQty, 'cartCount' => $cartCount]);
