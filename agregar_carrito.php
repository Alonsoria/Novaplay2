<?php
/**
 * NOVAPLAY — AGREGAR_CARRITO.PHP
 * Endpoint AJAX para añadir productos al carrito.
 * Solo acepta POST. Requiere sesión activa.
 */

require_once 'security.php';
require_once 'config.php';

header('Content-Type: application/json');

/* Solo POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

/* Requiere sesión */
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$uid        = (int)$_SESSION['user_id'];
$productoId = clean_int($_POST['producto_id'] ?? null);

if (!$productoId || $productoId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto inválido']);
    exit;
}

/* Verificar que el producto existe */
$stmt = $conn->prepare("SELECT id_producto FROM productos WHERE id_producto = ?");
$stmt->bind_param("i", $productoId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Producto no encontrado']);
    $stmt->close();
    exit;
}
$stmt->close();

/* Verificar si ya está en el carrito (query original) */
$stmt2 = $conn->prepare("SELECT id, cantidad FROM carrito WHERE id_usuario = ? AND id_producto = ?");
$stmt2->bind_param("ii", $uid, $productoId);
$stmt2->execute();
$res = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if ($res) {
    /* Aumentar cantidad */
    $nuevaCantidad = (int)$res['cantidad'] + 1;
    $stmt3 = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ?");
    $stmt3->bind_param("ii", $nuevaCantidad, $res['id']);
    $stmt3->execute();
    $stmt3->close();
} else {
    /* Insertar nuevo item */
    $stmt4 = $conn->prepare("INSERT INTO carrito (id_usuario, id_producto, cantidad) VALUES (?, ?, 1)");
    $stmt4->bind_param("ii", $uid, $productoId);
    $stmt4->execute();
    $stmt4->close();
}

/* Conteo total del carrito */
$stmtCount = $conn->prepare("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = ?");
$stmtCount->bind_param("i", $uid);
$stmtCount->execute();
$cartCount = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

echo json_encode(['success' => true, 'cartCount' => $cartCount]);
