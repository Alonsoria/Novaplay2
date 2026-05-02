<?php
/**
 * NOVAPLAY — CARRITO.PHP
 * Vista del carrito de compras. Requiere sesión.
 *
 * IMPORTANTE: Toda lógica que llame a header() debe ejecutarse ANTES
 * de require_once 'header.php', ya que ese archivo emite HTML.
 */

/* ── Cargar seguridad y DB sin emitir HTML aún ── */
require_once 'security.php';   /* inicia sesión, define require_login() */
require_once 'config.php';     /* $conn disponible */
require_login();               /* redirige a login si no hay sesión */

$uid = (int)$_SESSION['user_id'];

/* ── Puntos disponibles del usuario ── */
$userPuntos = 0;
try {
    $stmtPts = $conn->prepare("SELECT puntos FROM usuarios WHERE id_usuario = ?");
    $stmtPts->bind_param("i", $uid);
    $stmtPts->execute();
    $userPuntos = (int)($stmtPts->get_result()->fetch_assoc()['puntos'] ?? 0);
    $stmtPts->close();
} catch (Exception $e) {}

/* ── Aplicar / quitar puntos (POST) — debe ir ANTES del HTML ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['puntos_action'])) {
    if ($_POST['puntos_action'] === 'apply') {
        $pAplicar = max(0, (int)($_POST['puntos_a_usar'] ?? 0));
        $pAplicar = min($pAplicar, $userPuntos);
        if ($pAplicar > 0) {
            $_SESSION['puntos_usados'] = $pAplicar;
        } else {
            unset($_SESSION['puntos_usados']);
        }
    } else {
        unset($_SESSION['puntos_usados']);
    }
    header('Location: carrito.php');
    exit;
}

/* Puntos aplicados en esta compra */
$puntosUsados = min((int)($_SESSION['puntos_usados'] ?? 0), $userPuntos);

/* ── Ahora sí incluir header (emite HTML a partir de aquí) ── */
$pageTitle = 'Carrito';
require_once 'header.php';   /* security.php y config.php no se recargan (require_once) */

/* ── Cargar items del carrito ── */
$items = [];
$result = $conn->query(
    "SELECT c.id, c.cantidad, p.id_producto AS producto_id, p.nombre, p.precio, p.imagen
     FROM carrito c
     JOIN productos p ON c.id_producto = p.id_producto
     WHERE c.id_usuario = $uid"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$total         = array_reduce($items, fn($carry, $item) => $carry + ($item['precio'] * $item['cantidad']), 0.0);
$descuentoPts  = min($puntosUsados, $total);          /* no puede superar el total */
$totalFinal    = max(0.0, $total - $descuentoPts);
$cashback      = (int)floor($totalFinal * 0.10);

/* Mensaje de error de pago (si viene de procesar_pago.php) */
$pagoError = '';
if (!empty($_SESSION['pago_error'])) {
    $pagoError = $_SESSION['pago_error'];
    unset($_SESSION['pago_error']);
}
?>

<main class="cart-page" aria-label="Carrito de compras">

  <h2><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Mi Carrito</h2>

  <?php if (!empty($pagoError)): ?>
    <div class="alert alert--error" role="alert" style="margin-bottom:var(--space-lg);">
      <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
      <?= e($pagoError) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($items)): ?>

    <!-- Carrito vacío — sadMario -->
    <div class="empty-cart">
      <img class="imagenMario" src="./images/sadMario.png" alt="Carrito vacío">
      <p>Vaya! parece que tus videojuegos están en otro castillo...</p>
      <a href="productos.php" class="btn mt-md">
        <i class="fa-solid fa-gamepad"></i> Ver juegos
      </a>
    </div>

  <?php else: ?>

    <!-- Layout flex: tabla izquierda + resumen derecha -->
    <div class="cart-layout">

      <!-- Tabla de productos -->
      <div class="cart-table-wrap" style="overflow-x:auto;">
        <table class="cart-table" aria-label="Productos en el carrito">
          <thead>
            <tr>
              <th scope="col">Producto</th>
              <th scope="col">Precio</th>
              <th scope="col">Cantidad</th>
              <th scope="col">Subtotal</th>
              <th scope="col"><span class="sr-only">Acciones</span></th>
            </tr>
          </thead>
          <tbody id="cartBody">
            <?php foreach ($items as $item): ?>
              <tr id="row-<?= (int)$item['id'] ?>">
                <td>
                  <div class="product-cell">
                    <?php if (!empty($item['imagen'])): ?>
                      <img src="<?= e($item['imagen']) ?>" alt="<?= e($item['nombre']) ?>">
                    <?php endif; ?>
                    <span><?= e($item['nombre']) ?></span>
                  </div>
                </td>
                <td>$<?= number_format((float)$item['precio'], 2) ?></td>
                <td>
                  <div class="cart-qty">
                    <button aria-label="Disminuir" class="qty-btn" data-action="decrease" data-id="<?= (int)$item['id'] ?>">−</button>
                    <span id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['cantidad'] ?></span>
                    <button aria-label="Aumentar"  class="qty-btn" data-action="increase" data-id="<?= (int)$item['id'] ?>">+</button>
                  </div>
                </td>
                <td id="subtotal-<?= (int)$item['id'] ?>">
                  $<?= number_format((float)$item['precio'] * (int)$item['cantidad'], 2) ?>
                </td>
                <td>
                  <button class="btn btn--danger" style="padding:6px 12px;font-size:.8rem;"
                          aria-label="Eliminar <?= e($item['nombre']) ?>"
                          data-action="remove" data-id="<?= (int)$item['id'] ?>">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Resumen del pedido -->
      <div class="cart-summary">
        <div class="cart-summary-box">

          <div class="cart-summary-row">
            <span class="label">Subtotal</span>
            <span id="cartTotal">$<?= number_format($total, 2) ?></span>
          </div>
          <div class="cart-summary-row">
            <span class="label">Envío</span>
            <span class="text-accent">Gratis</span>
          </div>

          <!-- ── Canje de puntos ── -->
          <?php if ($userPuntos > 0): ?>
          <div class="puntos-canje-box" style="margin:10px 0;padding:12px 14px;
               background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.3);
               border-radius:10px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
              <i class="fa-solid fa-star" style="color:var(--clr-warning);" aria-hidden="true"></i>
              <span style="font-size:.88rem;font-weight:600;color:var(--clr-white);">
                Tus puntos: <strong style="color:var(--clr-neon);"><?= number_format($userPuntos) ?></strong>
                <span style="color:var(--clr-text-muted);font-weight:400;">(1 punto = $1 MXN)</span>
              </span>
            </div>

            <?php if ($puntosUsados > 0): ?>
              <!-- Puntos ya aplicados -->
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span style="font-size:.85rem;color:var(--clr-success);">
                  <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                  −$<?= number_format($descuentoPts, 2) ?> aplicados
                </span>
                <form method="POST" action="carrito.php" style="margin:0;">
                  <input type="hidden" name="puntos_action" value="remove">
                  <button type="submit"
                          style="background:none;border:1px solid var(--clr-border);
                                 border-radius:6px;padding:3px 10px;font-size:.78rem;
                                 color:var(--clr-text-muted);cursor:pointer;">
                    Quitar
                  </button>
                </form>
              </div>
            <?php else: ?>
              <!-- Formulario para aplicar puntos -->
              <form method="POST" action="carrito.php"
                    style="display:flex;gap:6px;align-items:center;"
                    id="formPuntos">
                <input type="hidden" name="puntos_action" value="apply">
                <input type="number"
                       name="puntos_a_usar"
                       id="puntosInput"
                       min="1"
                       max="<?= $userPuntos ?>"
                       placeholder="Puntos a usar"
                       style="flex:1;padding:6px 10px;border-radius:6px;
                              border:1px solid var(--clr-border);
                              background:var(--clr-surface-2);color:var(--clr-white);
                              font-size:.85rem;"
                       aria-label="Cantidad de puntos a canjear">
                <button type="submit"
                        style="background:var(--clr-accent);border:none;
                               border-radius:6px;padding:6px 12px;
                               color:#fff;font-size:.82rem;cursor:pointer;
                               white-space:nowrap;">
                  Aplicar
                </button>
              </form>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Cashback -->
          <div class="cashback-info">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <div>
              <span class="cashback-label">Cashback de esta compra</span>
              <span class="cashback-value" id="cashbackVal">+<?= $cashback ?> puntos</span>
            </div>
          </div>

          <hr style="border:none;border-top:1px solid var(--clr-border);">

          <?php if ($descuentoPts > 0): ?>
          <div class="cart-summary-row" style="font-size:.88rem;">
            <span class="label" style="color:var(--clr-text-muted);">Subtotal</span>
            <span style="color:var(--clr-text-muted);" id="cartTotal">$<?= number_format($total, 2) ?></span>
          </div>
          <div class="cart-summary-row" style="font-size:.88rem;">
            <span class="label" style="color:var(--clr-success);">
              <i class="fa-solid fa-tag" aria-hidden="true"></i> Descuento puntos
            </span>
            <span style="color:var(--clr-success);" id="cartDescuento">−$<?= number_format($descuentoPts, 2) ?></span>
          </div>
          <?php else: ?>
          <div class="cart-summary-row">
            <span class="label">Subtotal</span>
            <span id="cartTotal">$<?= number_format($total, 2) ?></span>
          </div>
          <?php endif; ?>

          <div class="cart-summary-row">
            <span class="label" style="font-weight:700;color:var(--clr-white);">Total</span>
            <span class="total" id="cartTotalFinal">$<?= number_format($totalFinal, 2) ?></span>
          </div>

          <!-- Métodos de pago -->
          <button type="button" class="btn btn-full pay-trigger" data-href="pago_tarjeta.php" style="margin-top:8px;">
            <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
            Pagar con Tarjeta
          </button>
          <button type="button" class="btn-paypal btn-full pay-trigger" data-href="crear_pago.php" style="margin-top:8px;">
            <i class="fa-brands fa-paypal" aria-hidden="true"></i>
            Pagar con PayPal
          </button>

        </div>
      </div>

    </div><!-- /.cart-layout -->

  <?php endif; ?>

</main>

<script>
(function () {
  const precios = {
    <?php foreach ($items as $item): ?>
      '<?= (int)$item['id'] ?>': <?= (float)$item['precio'] ?>,
    <?php endforeach; ?>
  };

  const puntosUsados = <?= (int)$puntosUsados ?>;   /* puntos aplicados en sesión */

  function updateTotals() {
    let subtotal = 0;
    document.querySelectorAll('[id^="qty-"]').forEach(function (el) {
      const id  = el.id.replace('qty-', '');
      const qty = parseInt(el.textContent, 10);
      const sub = (precios[id] || 0) * qty;
      subtotal += sub;
      const subEl = document.getElementById('subtotal-' + id);
      if (subEl) subEl.textContent = '$' + sub.toFixed(2);
    });

    const descuento  = Math.min(puntosUsados, subtotal);
    const totalFinal = Math.max(0, subtotal - descuento);

    /* Subtotal (sin descuento) */
    const cartTotalEl = document.getElementById('cartTotal');
    if (cartTotalEl) cartTotalEl.textContent = '$' + subtotal.toFixed(2);

    /* Línea de descuento (si existe) */
    const descEl = document.getElementById('cartDescuento');
    if (descEl) descEl.textContent = '−$' + descuento.toFixed(2);

    /* Total final */
    document.getElementById('cartTotalFinal').textContent = '$' + totalFinal.toFixed(2);

    /* Cashback sobre el total final */
    const cbEl = document.getElementById('cashbackVal');
    if (cbEl) cbEl.textContent = '+' + Math.floor(totalFinal * 0.10) + ' puntos';
  }

  document.querySelectorAll('.qty-btn, [data-action="remove"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id     = btn.dataset.id;
      const action = btn.dataset.action;

      fetch('actualizar_carrito.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'id=' + encodeURIComponent(id) + '&action=' + encodeURIComponent(action),
      })
      .then(r => r.json())
      .then(function (data) {
        if (action === 'remove' || data.qty <= 0) {
          const row = document.getElementById('row-' + id);
          if (row) row.remove();
          delete precios[id];
        } else {
          const qtyEl = document.getElementById('qty-' + id);
          if (qtyEl) qtyEl.textContent = data.qty;
        }
        updateTotals();
        if (data.cartCount !== undefined) {
          const badge = document.getElementById('cartBadge');
          if (badge) {
            badge.textContent = data.cartCount;
            badge.classList.toggle('d-none', data.cartCount === 0);
          }
        }
      })
      .catch(function () { location.reload(); });
    });
  });

  /* Validación: si total = 0, recargar en lugar de ir al pago */
  document.querySelectorAll('.pay-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const totalText = (document.getElementById('cartTotalFinal') || {}).textContent || '0';
      const totalNum  = parseFloat(totalText.replace(/[^0-9.]/g, ''));
      if (!totalNum || totalNum <= 0) {
        location.reload();
        return;
      }
      window.location.href = btn.dataset.href;
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
