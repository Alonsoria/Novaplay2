<?php
/**
 * NOVAPLAY — CANCEL.PHP
 * Página de cancelación del pago con PayPal.
 */

$pageTitle = 'Pago Cancelado';
require_once 'header.php';
?>

<div class="result-page" aria-label="Pago cancelado">
  <div class="result-icon" aria-hidden="true"><i class="fa-solid fa-circle-xmark" style="color:var(--clr-warning);"></i></div>
  <h2 style="color:var(--clr-warning);">Pago Cancelado</h2>
  <p>Tu pago fue cancelado. Los productos siguen en tu carrito cuando quieras completar la compra.</p>
  <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;justify-content:center;margin-top:var(--space-md);">
    <a href="carrito.php" class="btn">
      <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
      Volver al carrito
    </a>
    <a href="productos.php" class="btn2">
      <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
      Ver juegos
    </a>
  </div>
</div>

<?php require_once 'footer.php'; ?>
