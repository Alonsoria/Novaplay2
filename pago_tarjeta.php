<?php
/**
 * NOVAPLAY — PAGO_TARJETA.PHP
 * Formulario de pago con tarjeta. Requiere sesión activa.
 */

$pageTitle = 'Pago con Tarjeta';
require_once 'header.php';
require_login();
?>

<main class="payment-page" aria-label="Pago con tarjeta">

  <h2><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Pago con Tarjeta</h2>

  <!-- Vista previa animada de la tarjeta -->
  <div class="credit-card-preview" aria-hidden="true">
    <div class="card-number-display" id="previewNumber">•••• •••• •••• ••••</div>
    <div class="card-info-row">
      <div>
        <div class="card-info-label">Titular</div>
        <div class="card-info-value" id="previewName">NOMBRE APELLIDO</div>
      </div>
      <div>
        <div class="card-info-label">Vence</div>
        <div class="card-info-value" id="previewExpiry">MM/AA</div>
      </div>
    </div>
  </div>

  <!-- Formulario de pago -->
  <form class="payment-form"
        method="POST"
        action="procesar_pago.php"
        id="paymentForm"
        novalidate
        aria-label="Formulario de pago con tarjeta">

    <?= csrf_field() ?>

    <div class="form-group">
      <label for="card_name">Nombre del titular</label>
      <input type="text"
             id="card_name"
             name="card_name"
             class="form-control"
             placeholder="Como aparece en la tarjeta"
             required
             maxlength="60"
             autocomplete="cc-name">
    </div>

    <div class="form-group">
      <label for="card_number">Número de tarjeta</label>
      <input type="text"
             id="card_number"
             name="card_number"
             class="form-control"
             placeholder="1234 5678 9012 3456"
             required
             maxlength="19"
             inputmode="numeric"
             autocomplete="cc-number">
    </div>

    <div class="payment-row">
      <div class="form-group">
        <label for="card_expiry">Fecha de vencimiento</label>
        <input type="text"
               id="card_expiry"
               name="card_expiry"
               class="form-control"
               placeholder="MM/AA"
               required
               maxlength="5"
               inputmode="numeric"
               autocomplete="cc-exp">
      </div>
      <div class="form-group">
        <label for="card_cvv">CVV</label>
        <input type="password"
               id="card_cvv"
               name="card_cvv"
               class="form-control"
               placeholder="•••"
               required
               maxlength="4"
               inputmode="numeric"
               autocomplete="cc-csc">
      </div>
    </div>

    <!-- Nota de seguridad -->
    <p style="font-size:.82rem;color:var(--clr-text-muted);display:flex;align-items:center;gap:6px;">
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      Pago 100% seguro. Tus datos están cifrados.
    </p>

    <button type="submit" class="btn btn-full" id="payBtn">
      <i class="fa-solid fa-lock" aria-hidden="true"></i>
      Confirmar Pago
    </button>

    <a href="carrito.php" class="btn2 btn-full" style="margin-top:8px;text-align:center;">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
      Volver al carrito
    </a>

  </form>

</main>

<script>
(function () {
  const numInput    = document.getElementById('card_number');
  const nameInput   = document.getElementById('card_name');
  const expiryInput = document.getElementById('card_expiry');

  const previewNum    = document.getElementById('previewNumber');
  const previewName   = document.getElementById('previewName');
  const previewExpiry = document.getElementById('previewExpiry');

  /* Formatear número de tarjeta: grupos de 4 */
  numInput.addEventListener('input', function () {
    let val = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = val.replace(/(.{4})/g, '$1 ').trim();
    previewNum.textContent = this.value || '•••• •••• •••• ••••';
  });

  nameInput.addEventListener('input', function () {
    previewName.textContent = this.value.toUpperCase() || 'NOMBRE APELLIDO';
  });

  /* Formatear expiración MM/AA */
  expiryInput.addEventListener('input', function () {
    let val = this.value.replace(/\D/g, '').substring(0, 4);
    if (val.length >= 3) val = val.slice(0, 2) + '/' + val.slice(2);
    this.value = val;
    previewExpiry.textContent = this.value || 'MM/AA';
  });

  /* Prevenir submit doble */
  document.getElementById('paymentForm').addEventListener('submit', function () {
    const btn = document.getElementById('payBtn');
    btn.disabled     = true;
    btn.innerHTML    = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
  });
})();
</script>

<?php require_once 'footer.php'; ?>
