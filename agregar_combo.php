<?php
/**
 * NOVAPLAY — AGREGAR_COMBO.PHP
 * Endpoint AJAX: añade todos los productos de un combo al carrito.
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

$uid     = (int)$_SESSION['user_id'];
$comboId = clean_int($_POST['combo_id'] ?? null);

if (!$comboId || $comboId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de combo inválido']);
    exit;
}

/* Verificar que el combo existe y está activo */
$stmt = $conn->prepare("SELECT id_combo FROM combos WHERE id_combo = ? AND activo = 1");
$stmt->bind_param("i", $comboId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Combo no encontrado']);
    $stmt->close();
    exit;
}
$stmt->close();

/* Obtener los productos del combo */
$stmtProds = $conn->prepare("SELECT id_producto FROM combo_relacion WHERE id_combo = ?");
$stmtProds->bind_param("i", $comboId);
$stmtProds->execute();
$resProds = $stmtProds->get_result();
$productos = [];
while ($row = $resProds->fetch_assoc()) {
    $productos[] = (int)$row['id_producto'];
}
$stmtProds->close();

/* Añadir cada producto al carrito */
foreach ($productos as $productoId) {
    $stmtCheck = $conn->prepare("SELECT id, cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
    $stmtCheck->bind_param("ii", $uid, $productoId);
    $stmtCheck->execute();
    $existing = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        $newQty    = (int)$existing['cantidad'] + 1;
        $stmtUpd   = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
        $stmtUpd->bind_param("ii", $newQty, $existing['id']);
        $stmtUpd->execute();
        $stmtUpd->close();
    } else {
        $stmtIns = $conn->prepare("INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, 1)");
        $stmtIns->bind_param("ii", $uid, $productoId);
        $stmtIns->execute();
        $stmtIns->close();
    }
}

/* Devolver el conteo total del carrito */
$stmtCount = $conn->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = ?");
$stmtCount->bind_param("i", $uid);
$stmtCount->execute();
$cartCount = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

echo json_encode(['success' => true, 'cartCount' => $cartCount]);
