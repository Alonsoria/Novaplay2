<?php
/**
 * NOVAPLAY — SUCCESS.PHP
 * Página post-pago: muestra el cashback ganado y permite confirmar productos
 * usando el mismo modal compartido que el historial de pedidos en perfil.php.
 */

$pageTitle = 'Pago Exitoso';
require_once 'header.php';
require_login();

$uid      = (int)$_SESSION['user_id'];
$cashback = (int)($_SESSION['pago_cashback']  ?? 0);
$pedidoId = (int)($_SESSION['pago_pedido_id'] ?? 0);
unset($_SESSION['pago_exitoso'], $_SESSION['pago_cashback'], $_SESSION['pago_pedido_id']);

/* Cargar productos del pedido para el modal (sin códigos) */
$mpProductos = [];
if ($pedidoId) {
    $stmtProd = $conn->prepare(
        "SELECT ca.nombre_producto, ca.imagen_producto,
                COALESCE(GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ','), '') AS plataformas
         FROM codigos_activacion ca
         LEFT JOIN producto_plataforma pp ON ca.id_producto = pp.id_producto
         WHERE ca.id_pedido = ?
         GROUP BY ca.id, ca.nombre_producto, ca.imagen_producto
         ORDER BY ca.id"
    );
    $stmtProd->bind_param("i", $pedidoId);
    $stmtProd->execute();
    $res = $stmtProd->get_result();
    while ($row = $res->fetch_assoc()) {
        $plats = $row['plataformas'] !== ''
            ? array_map('intval', explode(',', $row['plataformas']))
            : [];
        $mpProductos[$pedidoId][] = [
            'nombre_producto' => $row['nombre_producto'],
            'imagen_producto' => $row['imagen_producto'],
            'plataformas'     => $plats,
        ];
    }
    $stmtProd->close();
}
$mpCodigos = []; /* Aún no confirmado — sin códigos */
?>

<div class="result-page" aria-label="Pago exitoso">

  <!-- Icono de éxito -->
  <div class="result-icon" aria-hidden="true">
    <i class="fa-solid fa-circle-check" style="color:var(--clr-success);"></i>
  </div>
  <h2 style="color:var(--clr-success);">¡Pago Exitoso!</h2>
  <p>Tu compra fue procesada correctamente.</p>

  <!-- Cashback pendiente (se acredita al confirmar) -->
  <?php if ($cashback > 0): ?>
    <p style="font-size:.95rem;color:var(--clr-neon);font-family:var(--font-display);">
      <i class="fa-solid fa-coins" style="color:var(--clr-warning);margin-right:4px;" aria-hidden="true"></i>
      ¡Ganarás <strong><?= $cashback ?> puntos</strong> al confirmar tus productos!
    </p>
  <?php endif; ?>

  <!-- Aviso + botón de confirmación -->
  <?php if ($pedidoId): ?>
    <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.35);
                border-radius:12px;padding:22px 24px;max-width:480px;width:100%;
                margin:var(--space-lg) auto 0;text-align:center;">
      <i class="fa-solid fa-key"
         style="font-size:2rem;color:var(--clr-accent);margin-bottom:10px;display:block;"
         aria-hidden="true"></i>
      <p class="suc-prod-title" style="color:var(--clr-white);font-size:.95rem;font-weight:600;margin:0 0 6px;">
        Tus productos están listos
      </p>
      <p style="color:var(--clr-text-muted);font-size:.87rem;margin:0 0 18px;line-height:1.5;">
        Confirma que los recibiste para desbloquear los códigos de activación.
      </p>
      <button id="suc-btn-confirmar"
              style="background:linear-gradient(135deg,var(--clr-accent),var(--clr-accent-2));
                     border:none;border-radius:10px;padding:11px 26px;color:#fff;
                     font-size:.95rem;font-weight:600;cursor:pointer;display:inline-flex;
                     align-items:center;gap:8px;">
        <i class="fa-solid fa-unlock" aria-hidden="true"></i>
        Confirmar mis productos
      </button>
      <p id="suc-confirmado-msg" style="display:none;margin:14px 0 0;
         color:var(--clr-success);font-size:.9rem;font-weight:600;">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        ¡Confirmado! Revisa tu correo para una copia de tus códigos.
      </p>
    </div>
  <?php endif; ?>

  <!-- Navegación -->
  <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;justify-content:center;
              margin-top:var(--space-lg);">
    <a href="perfil.php#pedidos" class="btn">
      <i class="fa-solid fa-box" aria-hidden="true"></i>
      Ver mis pedidos
    </a>
    <a href="productos.php" class="btn2">
      <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
      Seguir comprando
    </a>
    <?php if ($pedidoId): ?>
    <a href="perfil.php#pedidos"
       style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
              border-radius:var(--radius-sm);border:1px solid rgba(239,68,68,.5);
              color:rgba(239,68,68,.9);font-size:.9rem;font-weight:600;
              background:rgba(239,68,68,.08);text-decoration:none;transition:background .2s;"
       onmouseover="this.style.background='rgba(239,68,68,.18)'"
       onmouseout="this.style.background='rgba(239,68,68,.08)'">
      <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
      Solicitar reembolso
    </a>
    <?php endif; ?>
  </div>

</div>

<?php
/* Incluir el componente modal compartido */
require_once '_modal_confirmacion.php';
?>

<script>
(function () {
  var pedidoId = <?= (int)$pedidoId ?>;
  if (!pedidoId) return;

  /* Callback: ocultar botón y mostrar mensaje de éxito con puntos ganados */
  window._mpOnConfirm = function (pid, prods, resp) {
    var btn = document.getElementById('suc-btn-confirmar');
    if (btn) btn.style.display = 'none';
    var msg = document.getElementById('suc-confirmado-msg');
    if (msg) {
      if (resp && resp.cashback > 0) {
        msg.innerHTML =
          '<i class="fa-solid fa-circle-check" aria-hidden="true"></i>' +
          ' ¡Confirmado! Ganaste <strong>' + resp.cashback + ' puntos</strong>. ' +
          'Revisa tu correo para una copia de tus códigos.';
      }
      msg.style.display = '';
    }
  };

  /* Botón "Confirmar mis productos" → abre el modal compartido */
  var btn = document.getElementById('suc-btn-confirmar');
  if (btn) {
    btn.addEventListener('click', function () {
      window.abrirModalPedido(pedidoId, pedidoId);
    });
  }
})();
</script>

<?php require_once 'footer.php'; ?>
