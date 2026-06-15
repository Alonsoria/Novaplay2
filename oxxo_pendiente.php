<?php
/**
 * NOVAPLAY — OXXO_PENDIENTE.PHP
 * Muestra las instrucciones de pago en tienda (Conekta Efectivo) al cliente.
 * Accesible vía sesión (tras crear el pedido) o vía GET ?pedido_id=X (desde perfil).
 */

require_once 'security.php';
require_once 'config.php';

require_login();

$uid = (int)$_SESSION['user_id'];

/* ── Resolver pedido: sesión tiene prioridad, GET como fallback ── */
$pedidoId   = (int)($_SESSION['oxxo_pedido_id']    ?? clean_int($_GET['pedido_id'] ?? null));
$referencia  = $_SESSION['oxxo_referencia']   ?? null;
$barcodeUrl  = $_SESSION['oxxo_barcode_url']  ?? null;
$redirectUrl = $_SESSION['oxxo_redirect_url'] ?? null;
$expiraTs    = (int)($_SESSION['oxxo_expira']   ?? 0);
$total       = (float)($_SESSION['oxxo_total']  ?? 0);

if (!$pedidoId) {
    header('Location: perfil.php#pedidos');
    exit;
}

/* Verificar en BD que el pedido pertenece al usuario y sigue pendiente */
$stmtPed = $conn->prepare(
    "SELECT oxxo_referencia, oxxo_barcode_url, oxxo_expira, total
     FROM pedidos WHERE id_pedido = ? AND id_usuario = ? AND estado = 'pendiente_oxxo'"
);
$stmtPed->bind_param("ii", $pedidoId, $uid);
$stmtPed->execute();
$pedidoRow = $stmtPed->get_result()->fetch_assoc();
$stmtPed->close();

if (!$pedidoRow) {
    /* Ya fue pagado, cancelado o no existe */
    header('Location: perfil.php#pedidos');
    exit;
}

/* ── Cancelar si el tiempo de pago ya venció ── */
$expiraTsCheck = (int)strtotime($pedidoRow['oxxo_expira']);
if ($expiraTsCheck > 0 && $expiraTsCheck < time()) {
    $stmtCan = $conn->prepare(
        "UPDATE pedidos SET estado = 'cancelado'
         WHERE id_pedido = ? AND estado = 'pendiente_oxxo'"
    );
    $stmtCan->bind_param("i", $pedidoId);
    $stmtCan->execute();
    $stmtCan->close();
    $_SESSION['oxxo_pedido_id'] = null;
    header('Location: perfil.php#pedidos');
    exit;
}

/* Completar con datos de BD si la sesión no los tiene (acceso desde perfil) */
$referencia = $referencia ?: $pedidoRow['oxxo_referencia'];
$barcodeUrl = $barcodeUrl ?: $pedidoRow['oxxo_barcode_url'];
/* redirect_url solo existe en sesión (generada al crear el pedido) */
$expiraTs   = $expiraTs   ?: (int)strtotime($pedidoRow['oxxo_expira']);
$total      = $total      ?: (float)$pedidoRow['total'];

$tiendas = [
    'BBVA', '7-Eleven', 'Farmacias del Ahorro', 'CIRCLE K', 'Tiendas Extra',
    'Walmart', 'Bodega Aurrerá', "Sam's Club", 'Farmacia Benavides', 'Soriana',
    "WALDO'S", 'ELECZION', 'Super Kiosko', 'Farmacias Bazar', 'Woolworth',
    'Del Sol', 'Yepas', 'Farmacias De Dios', 'Farmacias Nosarco',
    'Farmacias Santa Cruz', 'Farmacentro', 'Farmacias GyM',
    'Farmacias San Francisco de Asís', 'Farmacias Unión', 'Farmacias Zapotlán',
    'Farmatodo', 'Alsúper',
];

$pageTitle = 'Pagar en tienda';
require_once 'header.php';
?>

<main class="result-page" aria-label="Instrucciones de pago en tienda" style="max-width:600px;margin:0 auto;">

  <!-- Encabezado -->
  <div style="text-align:center;margin-bottom:28px;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#0d9488,#0891b2);
                border-radius:50%;display:flex;align-items:center;justify-content:center;
                margin:0 auto 14px;box-shadow:0 0 28px rgba(13,148,136,.4);">
      <i class="fa-solid fa-store" style="font-size:1.8rem;color:#fff;" aria-hidden="true"></i>
    </div>
    <h2 style="font-family:var(--font-display);color:var(--clr-text);margin-bottom:4px;">
      Pagar en tienda
    </h2>
    <p style="color:var(--clr-text-muted);font-size:.9rem;">
      Pedido #<?= (int)$pedidoId ?> —
      <strong style="color:var(--clr-neon);">$<?= number_format($total, 2) ?> MXN</strong>
    </p>
  </div>

  <!-- Botón principal de instrucciones (cuando Conekta devuelve redirect_url) -->
  <?php if ($redirectUrl): ?>
  <div style="text-align:center;margin-bottom:24px;">
    <a href="<?= e($redirectUrl) ?>" target="_blank" rel="noopener noreferrer"
       style="display:inline-flex;align-items:center;gap:10px;
              background:linear-gradient(135deg,#0d9488,#0891b2);
              border:none;border-radius:12px;padding:14px 32px;color:#fff;
              font-size:1rem;font-weight:700;text-decoration:none;
              box-shadow:0 4px 20px rgba(13,148,136,.45);letter-spacing:.02em;">
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
      Ver instrucciones de pago
    </a>
    <p style="font-size:.78rem;color:var(--clr-text-muted);margin:8px 0 0;">
      Se abrirá la página de instrucciones de Conekta en una nueva pestaña.
    </p>
  </div>
  <?php endif; ?>

  <!-- Tarjeta de referencia -->
  <div style="background:var(--clr-surface);border:1px solid var(--clr-border);
              border-radius:16px;padding:28px 24px;margin-bottom:20px;text-align:center;">

    <?php if ($barcodeUrl): ?>
      <img src="<?= e($barcodeUrl) ?>"
           alt="Código de barras de pago"
           style="max-width:280px;width:100%;height:auto;display:block;margin:0 auto 20px;
                  background:#fff;padding:8px;border-radius:6px;"
           onerror="this.style.display='none'">
    <?php endif; ?>

    <!-- Número de referencia -->
    <p style="font-size:.8rem;color:var(--clr-text-muted);margin-bottom:6px;
              text-transform:uppercase;letter-spacing:.06em;">
      Número de referencia
    </p>
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;
                flex-wrap:wrap;margin-bottom:20px;">
      <span id="oxxo-ref"
            style="font-family:monospace;font-size:1.5rem;font-weight:700;
                   letter-spacing:4px;color:var(--clr-text);word-break:break-all;">
        <?= e($referencia) ?>
      </span>
      <button id="btn-copiar-ref"
              onclick="copiarReferencia()"
              style="background:none;border:1px solid var(--clr-border);border-radius:8px;
                     padding:5px 12px;color:var(--clr-text);font-size:.82rem;cursor:pointer;">
        <i class="fa-solid fa-copy" aria-hidden="true"></i> Copiar
      </button>
    </div>

    <!-- Vencimiento con countdown -->
    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);
                border-radius:10px;padding:14px 16px;">
      <p style="font-size:.85rem;color:var(--clr-warning);font-weight:600;margin:0 0 4px;">
        <i class="fa-solid fa-clock" aria-hidden="true"></i>
        Tiempo restante para pagar:
        <span id="oxxo-countdown" style="font-family:monospace;margin-left:6px;font-size:.95rem;"></span>
      </p>
      <p style="font-size:.78rem;color:var(--clr-text-muted);margin:0;">
        Vence el <?= date('d/m/Y H:i', $expiraTs) ?> hrs — <strong>72 horas para pagar</strong>
      </p>
    </div>
  </div>

  <!-- Instrucciones -->
  <div style="background:rgb(0 0 0 / 4%);border:1px solid var(--clr-border);
              border-radius:12px;padding:18px 20px;margin-bottom:20px;">
    <p style="font-size:.85rem;font-weight:600;color:var(--clr-text);margin:0 0 10px;">
      <i class="fa-solid fa-list-check" style="color:#0d9488;margin-right:6px;" aria-hidden="true"></i>
      ¿Cómo pagar en tienda?
    </p>
    <ol style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px;">
      <li style="font-size:.84rem;color:var(--clr-text-muted);">
        Acude a cualquiera de las tiendas participantes (ver lista abajo).
      </li>
      <li style="font-size:.84rem;color:var(--clr-text-muted);">
        Indica que deseas realizar un <strong style="color:var(--clr-text);">pago de servicios</strong>
        con referencia Conekta.
      </li>
      <li style="font-size:.84rem;color:var(--clr-text-muted);">
        Proporciona el número de referencia o muestra este código.
      </li>
      <li style="font-size:.84rem;color:var(--clr-text-muted);">
        Paga exactamente <strong style="color:var(--clr-neon);">$<?= number_format($total, 2) ?> MXN</strong>.
      </li>
    </ol>
  </div>

  <!-- Tiendas participantes -->
  <div style="background:var(--clr-surface);border:1px solid var(--clr-border);
              border-radius:12px;padding:18px 20px;margin-bottom:20px;">
    <p style="font-size:.85rem;font-weight:600;color:var(--clr-text);margin:0 0 12px;">
      <i class="fa-solid fa-map-location-dot" style="color:#0d9488;margin-right:6px;" aria-hidden="true"></i>
      Tiendas participantes
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach ($tiendas as $tienda): ?>
        <span style="background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);
                     border-radius:6px;padding:4px 10px;font-size:.78rem;
                     color:var(--clr-text);white-space:nowrap;">
          <?= e($tienda) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Aviso de códigos -->
  <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);
              border-radius:12px;padding:16px 18px;margin-bottom:24px;text-align:center;">
    <i class="fa-solid fa-key" style="color:var(--clr-accent);font-size:1.4rem;
       display:block;margin-bottom:8px;" aria-hidden="true"></i>
    <p style="font-size:.87rem;color:var(--clr-text);margin:0;line-height:1.6;">
      Una vez confirmado tu pago, podrás ver tus <strong>códigos de activación</strong>
      en <a href="perfil.php#pedidos" style="color:var(--clr-accent);">Perfil → Historial de pedidos</a>.
    </p>
  </div>

  <!-- ── SANDBOX: Botón de prueba ── -->
  <div style="background:rgba(245,158,11,.08);border:2px dashed rgba(245,158,11,.5);
              border-radius:12px;padding:18px 20px;margin-bottom:24px;text-align:center;">
    <p style="font-size:.75rem;font-weight:700;color:var(--clr-warning);
       text-transform:uppercase;letter-spacing:.08em;margin:0 0 8px;">
      <i class="fa-solid fa-flask" aria-hidden="true"></i>
      Entorno Sandbox — Solo para pruebas
    </p>
    <p style="font-size:.83rem;color:var(--clr-text-muted);margin:0 0 14px;">
      Este botón simula el pago exitoso en tienda de forma inmediata, sin dinero real.
    </p>
    <form method="POST" action="simular_pago_oxxo.php" id="form-simular-oxxo">
      <?= csrf_field() ?>
      <input type="hidden" name="pedido_id" value="<?= (int)$pedidoId ?>">
      <button type="submit"
              style="background:linear-gradient(135deg,#d97706,#b45309);border:none;
                     border-radius:10px;padding:12px 28px;color:#fff;
                     font-size:.95rem;font-weight:700;cursor:pointer;
                     display:inline-flex;align-items:center;gap:10px;
                     box-shadow:0 4px 18px rgba(217,119,6,.4);">
        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        SANDBOX — Pago exitoso
      </button>
    </form>
  </div>

  <!-- Navegación -->
  <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;justify-content:center;">
    <a href="perfil.php#pedidos" class="btn">
      <i class="fa-solid fa-box" aria-hidden="true"></i>
      Ver mis pedidos
    </a>
    <a href="productos.php" class="btn2">
      <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
      Seguir comprando
    </a>
  </div>

</main>

<script>
(function () {
  /* ── Countdown de 72 horas ── */
  var expiraTs = <?= (int)$expiraTs ?>;
  var el = document.getElementById('oxxo-countdown');

  var cancelado = false;

  function tick() {
    var diff = Math.max(0, expiraTs - Math.floor(Date.now() / 1000));
    if (diff === 0) {
      if (el) el.textContent = 'Expirado';
      if (!cancelado) {
        cancelado = true;
        fetch('cancelar_oxxo.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'pedido_id=<?= (int)$pedidoId ?>',
        }).finally(function () {
          /* Redirigir al perfil tras cancelar */
          setTimeout(function () {
            window.location.href = 'perfil.php#pedidos';
          }, 2500);
        });
      }
      return;
    }
    var h = Math.floor(diff / 3600);
    var m = Math.floor((diff % 3600) / 60);
    var s = diff % 60;
    if (el) {
      el.textContent =
        String(h).padStart(2, '0') + ':' +
        String(m).padStart(2, '0') + ':' +
        String(s).padStart(2, '0');
    }
  }

  tick();
  setInterval(tick, 1000);
})();

/* ── Copiar referencia ── */
function copiarReferencia() {
  var ref = document.getElementById('oxxo-ref');
  var btn = document.getElementById('btn-copiar-ref');
  if (!ref || !btn) return;
  navigator.clipboard.writeText(ref.textContent.trim()).then(function () {
    btn.innerHTML = '<i class="fa-solid fa-check"></i> ¡Copiado!';
    btn.style.color       = 'var(--clr-success)';
    btn.style.borderColor = 'var(--clr-success)';
    setTimeout(function () {
      btn.innerHTML         = '<i class="fa-solid fa-copy"></i> Copiar';
      btn.style.color       = '';
      btn.style.borderColor = '';
    }, 2500);
  });
}

/* ── Loading al enviar el formulario sandbox ── */
(function () {
  var form = document.getElementById('form-simular-oxxo');
  if (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled     = true;
        btn.innerHTML    = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando…';
      }
    });
  }
})();
</script>

<?php require_once 'footer.php'; ?>
