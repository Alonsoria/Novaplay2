<?php
/**
 * NOVAPLAY — PAGO_TARJETA.PHP
 * Formulario de pago con tarjeta. Requiere sesión activa.
 * Soporta tarjetas guardadas (autocomplete excepto CVV).
 */

$pageTitle = 'Pago con Tarjeta';
require_once 'header.php';
require_login();

$uid = (int)$_SESSION['user_id'];

/* Cargar tarjetas guardadas del usuario */
$savedCards = [];
try {
    $stmtSC = $conn->prepare(
        "SELECT id, ultimos4, marca, expiry FROM tarjetas_guardadas
         WHERE id_usuario = ? ORDER BY creado DESC"
    );
    $stmtSC->bind_param("i", $uid);
    $stmtSC->execute();
    $resSC = $stmtSC->get_result();
    while ($row = $resSC->fetch_assoc()) $savedCards[] = $row;
    $stmtSC->close();
} catch (Exception $e) {}

/* Error de pago previo */
$pagoError = '';
if (!empty($_SESSION['pago_error'])) {
    $pagoError = $_SESSION['pago_error'];
    unset($_SESSION['pago_error']);
}
?>

<main class="payment-page" aria-label="Pago con tarjeta">

  <div class="payment-header">
    <h2>
      <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
      Pago con Tarjeta
    </h2>
    <p style="color:var(--clr-text-muted);font-size:.9rem;margin-top:4px;">
      <i class="fa-solid fa-lock" style="color:var(--clr-success);" aria-hidden="true"></i>
      Conexión segura cifrada
    </p>
  </div>

  <?php if ($pagoError): ?>
    <div class="alert alert--error" role="alert" style="margin-bottom:var(--space-lg);">
      <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
      <?= e($pagoError) ?>
    </div>
  <?php endif; ?>

  <div class="payment-layout">

    <!-- ── Columna izquierda: Vista previa de tarjeta ── -->
    <div class="payment-card-col">

      <!-- Tarjeta visual 3D -->
      <div class="credit-card-preview" id="cardPreview" aria-hidden="true">
        <div class="card-chip">
          <div class="card-chip-lines"></div>
        </div>
        <div class="card-logo" id="previewBrand">
          <i class="fa-brands fa-credit-card"></i>
        </div>
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

      <!-- Seguridad info -->
      <div class="payment-security-badge">
        <i class="fa-solid fa-shield-halved" style="color:var(--clr-success);font-size:1.2rem;" aria-hidden="true"></i>
        <div>
          <div style="font-weight:700;font-size:.85rem;color:var(--clr-white);">Pago 100% seguro</div>
          <div style="font-size:.75rem;color:var(--clr-text-muted);">Cifrado SHA-256 · PCI DSS</div>
        </div>
        <div style="display:flex;gap:var(--space-sm);margin-left:auto;opacity:.7;">
          <i class="fa-brands fa-cc-visa" style="font-size:1.6rem;color:var(--clr-white);" aria-label="Visa"></i>
          <i class="fa-brands fa-cc-mastercard" style="font-size:1.6rem;color:var(--clr-white);" aria-label="Mastercard"></i>
          <i class="fa-brands fa-cc-amex" style="font-size:1.6rem;color:var(--clr-white);" aria-label="Amex"></i>
        </div>
      </div>
    </div>

    <!-- ── Columna derecha: Formulario ── -->
    <div class="payment-form-col">

      <!-- Tarjetas guardadas (si existen) -->
      <?php if (!empty($savedCards)): ?>
      <div class="saved-cards-section">
        <h4 class="saved-cards-title">
          <i class="fa-solid fa-wallet" aria-hidden="true"></i>
          Tarjetas guardadas
        </h4>
        <div class="saved-cards-list" role="radiogroup" aria-label="Seleccionar tarjeta guardada">

          <!-- Opción: nueva tarjeta (seleccionada por defecto) -->
          <label class="saved-card-option active" data-card-id="new" tabindex="0">
            <input type="radio" name="ui_saved_card" value="new" checked>
            <i class="fa-solid fa-plus" style="font-size:1.2rem;color:var(--clr-accent-2);" aria-hidden="true"></i>
            <span>Usar nueva tarjeta</span>
          </label>

          <?php foreach ($savedCards as $sc):
            $icono = match(strtolower($sc['marca'])) {
                'visa'       => 'fa-brands fa-cc-visa',
                'mastercard' => 'fa-brands fa-cc-mastercard',
                'amex'       => 'fa-brands fa-cc-amex',
                'discover'   => 'fa-brands fa-cc-discover',
                default      => 'fa-solid fa-credit-card',
            };
          ?>
          <label class="saved-card-option"
                 data-card-id="<?= (int)$sc['id'] ?>"
                 data-expiry="<?= e($sc['expiry']) ?>"
                 data-ultimos4="<?= e($sc['ultimos4']) ?>"
                 data-marca="<?= e($sc['marca']) ?>"
                 tabindex="0">
            <input type="radio" name="ui_saved_card" value="<?= (int)$sc['id'] ?>">
            <i class="<?= $icono ?>" style="font-size:1.6rem;" aria-hidden="true"></i>
            <div>
              <div style="font-weight:600;color:var(--clr-white);">
                <?= e($sc['marca']) ?> •••• <?= e($sc['ultimos4']) ?>
              </div>
              <div style="font-size:.78rem;color:var(--clr-text-muted);">Vence: <?= e($sc['expiry']) ?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Formulario de pago -->
      <form class="payment-form"
            method="POST"
            action="procesar_pago.php"
            id="paymentForm"
            novalidate
            aria-label="Formulario de pago">

        <?= csrf_field() ?>
        <!-- Tarjeta guardada seleccionada (hidden, se rellena por JS) -->
        <input type="hidden" id="saved_card_id_selected" name="saved_card_id_selected" value="0">

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

        <!-- Campos de nueva tarjeta (se ocultan al seleccionar tarjeta guardada) -->
        <div id="new-card-fields">
          <div class="form-group">
            <label for="card_number">Número de tarjeta</label>
            <div style="position:relative;">
              <input type="text"
                     id="card_number"
                     name="card_number"
                     class="form-control"
                     placeholder="1234 5678 9012 3456"
                     required
                     maxlength="19"
                     inputmode="numeric"
                     autocomplete="cc-number"
                     style="padding-right:3rem;">
              <span id="brandIcon"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:1.4rem;color:var(--clr-text-muted);"
                    aria-hidden="true">
                <i class="fa-solid fa-credit-card"></i>
              </span>
            </div>
          </div>

          <div class="payment-row">
            <div class="form-group">
              <label for="card_expiry">Fecha vencimiento</label>
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
              <label for="card_cvv">
                CVV
                <span style="color:var(--clr-text-muted);font-size:.78rem;font-weight:400;">
                  (siempre requerido)
                </span>
              </label>
              <input type="password"
                     id="card_cvv"
                     name="card_cvv"
                     class="form-control"
                     placeholder="•••"
                     required
                     maxlength="3"
                     inputmode="numeric"
                     autocomplete="cc-csc">
            </div>
          </div>
        </div>

        <!-- CVV para tarjeta guardada (se muestra solo cuando hay tarjeta guardada seleccionada) -->
        <div id="saved-card-cvv-wrap" style="display:none;">
          <div class="saved-card-cvv-notice">
            <i class="fa-solid fa-lock" style="color:var(--clr-accent-2);" aria-hidden="true"></i>
            <span>Tarjeta guardada. Ingresa el CVV para confirmar.</span>
          </div>
          <div class="payment-row">
            <div class="form-group" style="grid-column:1/-1;">
              <label for="card_cvv">CVV de la tarjeta guardada</label>
              <input type="password"
                     id="card_cvv_saved"
                     name="card_cvv"
                     class="form-control"
                     placeholder="•••"
                     maxlength="3"
                     inputmode="numeric"
                     autocomplete="cc-csc">
            </div>
          </div>
        </div>

        <!-- Guardar tarjeta (solo visible para nuevas tarjetas) -->
        <div id="save-card-wrap" class="save-card-checkbox">
          <input type="checkbox"
                 id="guardar_tarjeta"
                 name="guardar_tarjeta"
                 value="1">
          <div>
            <label for="guardar_tarjeta">
              <i class="fa-solid fa-shield-halved"
                 style="color:var(--clr-accent);"
                 aria-hidden="true"></i>
              Guardar tarjeta para futuras compras
            </label>
            <small>Solo guardamos los últimos 4 dígitos y fecha. Nunca el número completo ni el CVV.</small>
          </div>
        </div>

        <button type="submit" class="btn btn-full btn-pay" id="payBtn">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          Confirmar Pago
        </button>

        <a href="carrito.php" class="btn2 btn-full" style="margin-top:8px;text-align:center;">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          Volver al carrito
        </a>

      </form>
    </div><!-- /.payment-form-col -->
  </div><!-- /.payment-layout -->

</main>

<script>
(function () {
  /* ── Referencias ── */
  const numInput    = document.getElementById('card_number');
  const nameInput   = document.getElementById('card_name');
  const expiryInput = document.getElementById('card_expiry');
  const cvvInput    = document.getElementById('card_cvv');

  const previewNum    = document.getElementById('previewNumber');
  const previewName   = document.getElementById('previewName');
  const previewExpiry = document.getElementById('previewExpiry');
  const previewBrand  = document.getElementById('previewBrand');
  const brandIconEl   = document.getElementById('brandIcon');
  const savedCardHid  = document.getElementById('saved_card_id_selected');

  const newCardFields      = document.getElementById('new-card-fields');
  const savedCvvWrap       = document.getElementById('saved-card-cvv-wrap');
  const savedCvvInput      = document.getElementById('card_cvv_saved');
  const saveCardWrap       = document.getElementById('save-card-wrap');
  const cardPreview        = document.getElementById('cardPreview');

  /* ── Detectar marca por número ── */
  function detectBrand(num) {
    if (/^4/.test(num))          return { name: 'Visa',       icon: 'fa-brands fa-cc-visa',        color: '#1a1f71' };
    if (/^5[1-5]/.test(num))     return { name: 'Mastercard', icon: 'fa-brands fa-cc-mastercard',  color: '#eb001b' };
    if (/^3[47]/.test(num))      return { name: 'Amex',       icon: 'fa-brands fa-cc-amex',        color: '#007bc1' };
    if (/^6/.test(num))          return { name: 'Discover',   icon: 'fa-brands fa-cc-discover',    color: '#ff6600' };
    return { name: '', icon: 'fa-solid fa-credit-card', color: 'var(--clr-text-muted)' };
  }

  function applyBrand(brand) {
    if (brandIconEl) {
      brandIconEl.innerHTML = '<i class="' + brand.icon + '"></i>';
      brandIconEl.style.color = brand.color || 'var(--clr-text-muted)';
    }
    if (previewBrand) {
      previewBrand.innerHTML = '<i class="' + brand.icon + '"></i>';
    }
    /* Cambiar gradiente de la tarjeta según marca */
    const gradients = {
      'Visa':       'linear-gradient(135deg, #1a1f71 0%, #2d55b4 60%, #7c3aed 100%)',
      'Mastercard': 'linear-gradient(135deg, #eb001b 0%, #ff5f00 60%, #7c3aed 100%)',
      'Amex':       'linear-gradient(135deg, #007bc1 0%, #00b4d8 60%, #7c3aed 100%)',
      'Discover':   'linear-gradient(135deg, #ff6600 0%, #ffbe00 60%, #7c3aed 100%)',
    };
    if (cardPreview && brand.name && gradients[brand.name]) {
      cardPreview.style.background = gradients[brand.name];
    } else if (cardPreview) {
      cardPreview.style.background = '';
    }
  }

  /* ── Formatear número de tarjeta ── */
  if (numInput) {
    numInput.addEventListener('input', function () {
      let val = this.value.replace(/\D/g, '').substring(0, 16);
      this.value = val.replace(/(.{4})/g, '$1 ').trim();
      previewNum.textContent = this.value || '•••• •••• •••• ••••';
      applyBrand(detectBrand(val));
    });
  }

  /* ── Actualizar nombre en preview ── */
  if (nameInput) {
    nameInput.addEventListener('input', function () {
      previewName.textContent = this.value.toUpperCase() || 'NOMBRE APELLIDO';
    });
  }

  /* ── Formatear expiración MM/AA con validación ── */
  let isDeletingExpiry = false;
  if (expiryInput) {
    expiryInput.addEventListener('keydown', function (e) {
      isDeletingExpiry = e.key === 'Backspace' || e.key === 'Delete';
    });

    expiryInput.addEventListener('input', function () {
      let val = this.value.replace(/\D/g, '').substring(0, 4);

      /* Auto-completar con 0 para meses 2-9 (si no se está borrando) */
      if (!isDeletingExpiry && val.length === 1) {
        const m = parseInt(val, 10);
        if (m >= 2 && m <= 9) val = '0' + val;
      }

      /* Prevenir mes > 12 */
      if (val.length >= 2) {
        let month = parseInt(val.substring(0, 2), 10);
        if (month > 12) val = '12' + val.substring(2);
        if (month === 0) val = '01' + val.substring(2);
      }

      /* Insertar la barra */
      if (val.length >= 3) val = val.slice(0, 2) + '/' + val.slice(2);

      this.value = val;
      previewExpiry.textContent = val || 'MM/AA';
    });
  }

  /* ── Tarjetas guardadas: selección ── */
  document.querySelectorAll('.saved-card-option').forEach(function (label) {
    label.addEventListener('click', function () {
      activateSavedCard(label);
    });
    label.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        activateSavedCard(label);
      }
    });
  });

  function activateSavedCard(label) {
    /* Marcar visual */
    document.querySelectorAll('.saved-card-option').forEach(function (l) {
      l.classList.remove('active');
    });
    label.classList.add('active');
    const radio = label.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    const cardId   = label.dataset.cardId;
    const expiry   = label.dataset.expiry   || '';
    const ultimos4 = label.dataset.ultimos4 || '';
    const marca    = label.dataset.marca    || '';

    if (cardId === 'new') {
      /* Nueva tarjeta */
      savedCardHid.value = '0';
      newCardFields.style.display      = '';
      savedCvvWrap.style.display       = 'none';
      saveCardWrap.style.display       = '';
      if (numInput)    { numInput.readOnly = false; numInput.value = ''; }
      if (expiryInput) { expiryInput.readOnly = false; expiryInput.value = ''; }
      if (cvvInput)    cvvInput.value = '';
      previewNum.textContent    = '•••• •••• •••• ••••';
      previewExpiry.textContent = 'MM/AA';
      applyBrand({ name: '', icon: 'fa-solid fa-credit-card', color: 'var(--clr-text-muted)' });
      cardPreview.style.background = '';
    } else {
      /* Tarjeta guardada */
      savedCardHid.value = cardId;
      newCardFields.style.display      = 'none';
      savedCvvWrap.style.display       = '';
      saveCardWrap.style.display       = 'none';
      if (savedCvvInput) savedCvvInput.value = '';

      previewNum.textContent    = '•••• •••• •••• ' + ultimos4;
      previewExpiry.textContent = expiry;
      previewName.textContent   = nameInput ? (nameInput.value.toUpperCase() || 'NOMBRE APELLIDO') : 'TITULAR';
      applyBrand(detectBrand(
        marca.toLowerCase() === 'visa'       ? '4' :
        marca.toLowerCase() === 'mastercard' ? '51' :
        marca.toLowerCase() === 'amex'       ? '37' :
        marca.toLowerCase() === 'discover'   ? '6' : ''
      ));
    }
  }

  /* ── Prevenir submit doble y corregir CVV duplicado ── */
  document.getElementById('paymentForm').addEventListener('submit', function (e) {
    const cardId = savedCardHid.value;

    if (cardId !== '0') {
      /* Tarjeta guardada: deshabilitar campos de nueva tarjeta para que no
         envíen valores al servidor (evita que el CVV vacío de nueva tarjeta
         sobreescriba el CVV ingresado para la tarjeta guardada). */
      if (cvvInput)    { cvvInput.removeAttribute('required'); cvvInput.disabled = true; }
      if (numInput)    numInput.disabled    = true;
      if (expiryInput) expiryInput.disabled = true;
      if (savedCvvInput) savedCvvInput.setAttribute('required', '');
    } else {
      /* Nueva tarjeta: deshabilitar el CVV de tarjeta guardada (está oculto
         pero tiene el mismo name="card_cvv" → PHP usaría su valor vacío). */
      if (savedCvvInput) savedCvvInput.disabled = true;
    }

    const btn = document.getElementById('payBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
  });
})();
</script>

<?php require_once 'footer.php'; ?>
