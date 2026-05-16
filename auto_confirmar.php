<?php
/**
 * NOVAPLAY — AUTO_CONFIRMAR.PHP
 * Confirma automáticamente todos los ítems de pedidos con más de 24h sin confirmar.
 *
 * Ejecutar como cron job cada hora:
 *   0 * * * * php /path/to/novaplay2/auto_confirmar.php >> /var/log/novaplay_cron.log 2>&1
 *
 * También puede ejecutarse manualmente desde CLI:
 *   php auto_confirmar.php
 *
 * NUNCA exponer este archivo vía HTTP en producción.
 */

define('NV_CRON', true);

if (PHP_SAPI !== 'cli' && !defined('NV_INTERNAL')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

require_once dirname(__FILE__) . '/config.php';

$log = function (string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
};

$log('Iniciando auto-confirmación de pedidos expirados (> 24 h)...');

/* ── 1. Confirmar todos los ítems pendientes de pedidos expirados ── */
$conn->query(
    "UPDATE codigos_activacion ca
     JOIN pedidos p ON ca.id_pedido = p.id_pedido
     SET ca.confirmado = 1
     WHERE p.estado    = 'pagado'
       AND ca.devuelto = 0
       AND ca.confirmado = 0
       AND p.fecha < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);
$itemsConfirmados = $conn->affected_rows;

/* ── 2. Cerrar pedidos donde ya no quedan ítems pendientes ── */
$conn->query(
    "UPDATE pedidos p
     SET p.confirmado = 1
     WHERE p.estado     = 'pagado'
       AND p.confirmado = 0
       AND p.fecha < DATE_SUB(NOW(), INTERVAL 24 HOUR)
       AND NOT EXISTS (
           SELECT 1 FROM codigos_activacion ca
           WHERE ca.id_pedido  = p.id_pedido
             AND ca.devuelto   = 0
             AND ca.confirmado = 0
       )"
);
$pedidosCerrados = $conn->affected_rows;

/* ── 3. Acreditar cashback (10%) por los ítems auto-confirmados ──
   Agrupa por usuario y suma precios de los ítems recién confirmados
   en pedidos que acaban de expirar. */
$resCashback = $conn->query(
    "SELECT p.id_usuario, SUM(COALESCE(pr.precio, 0)) AS suma
     FROM codigos_activacion ca
     JOIN pedidos p  ON ca.id_pedido  = p.id_pedido
     JOIN productos pr ON ca.id_producto = pr.id_producto
     WHERE ca.confirmado = 1
       AND p.confirmado  = 1
       AND p.fecha < DATE_SUB(NOW(), INTERVAL 24 HOUR)
       AND p.fecha > DATE_SUB(NOW(), INTERVAL 25 HOUR)
     GROUP BY p.id_usuario"
);

$mesCurrent = date('Y-m');
$usuariosActualizados = 0;

if ($resCashback) {
    while ($row = $resCashback->fetch_assoc()) {
        $uid      = (int)$row['id_usuario'];
        $cashback = (int)floor((float)$row['suma'] * 0.10);
        if ($cashback <= 0) continue;

        $stmtMes = $conn->prepare("SELECT puntos_reset_mes FROM usuarios WHERE id_usuario = ?");
        $stmtMes->bind_param("i", $uid);
        $stmtMes->execute();
        $mesBD = $stmtMes->get_result()->fetch_assoc()['puntos_reset_mes'] ?? '';
        $stmtMes->close();

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
        $usuariosActualizados++;
    }
}

$log("Ítems auto-confirmados : {$itemsConfirmados}");
$log("Pedidos cerrados       : {$pedidosCerrados}");
$log("Usuarios con cashback  : {$usuariosActualizados}");
$log('Completado.');
