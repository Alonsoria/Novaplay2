<?php
/**
 * NOVAPLAY — SUCCESS.PHP
 * Página de éxito después del pago con PayPal.
 */

$pageTitle = 'Pago Exitoso';
require_once 'header.php';
require_login();

/* Limpiar carrito tras pago exitoso (query original) */
$uid = (int)$_SESSION['user_id'];
$conn->query("DELETE FROM carrito WHERE id_usuario = $uid");
?>

<div class="result-page" aria-label="Pago exitoso">
  <div class="result-icon" aria-hidden="true">🎉</div>
  <h2 style="color:var(--clr-success);">¡Pago Exitoso!</h2>
  <p>Tu compra fue procesada correctamente. Revisa tu correo para los detalles.</p>
  <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;justify-content:center;margin-top:var(--space-md);">
    <a href="productos.php" class="btn">
      <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
      Seguir comprando
    </a>
    <a href="index.php" class="btn2">
      <i class="fa-solid fa-house" aria-hidden="true"></i>
      Volver al inicio
    </a>
  </div>
</div>

<?php require_once 'footer.php'; ?>
