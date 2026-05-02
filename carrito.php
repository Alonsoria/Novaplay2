<?php
/**
 * NOVAPLAY — CARRITO.PHP
 * Vista del carrito de compras. Requiere sesión.
 */

$pageTitle = 'Carrito';
require_once 'header.php';
require_login();

$uid = (int)$_SESSION['user_id'];

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

$total    = array_reduce($items, fn($carry, $item) => $carry + ($item['precio'] * $item['cantidad']), 0.0);
$cashback = (int)floor($total * 0.10);

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

          <!-- Cashback -->
          <div class="cashback-info">
            <i class="fa-solid fa-coins" aria-hidden="true"></i>
            <div>
              <span class="cashback-label">Cashback de esta compra</span>
              <span class="cashback-value" id="cashbackVal">+<?= $cashback ?> puntos</span>
            </div>
          </div>

          <hr style="border:none;border-top:1px solid var(--clr-border);">

          <div class="cart-summary-row">
            <span class="label" style="font-weight:700;color:var(--clr-white);">Total</span>
            <span class="total" id="cartTotalFinal">$<?= number_format($total, 2) ?></span>
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

  function updateTotals() {
    let total = 0;
    document.querySelectorAll('[id^="qty-"]').forEach(function (el) {
      const id  = el.id.replace('qty-', '');
      const qty = parseInt(el.textContent, 10);
      const sub = (precios[id] || 0) * qty;
      total += sub;
      const subEl = document.getElementById('subtotal-' + id);
      if (subEl) subEl.textContent = '$' + sub.toFixed(2);
    });
    const fmt = '$' + total.toFixed(2);
    document.getElementById('cartTotal')     .textContent = fmt;
    document.getElementById('cartTotalFinal').textContent = fmt;

    /* Actualizar cashback */
    const cbEl = document.getElementById('cashbackVal');
    if (cbEl) cbEl.textContent = '+' + Math.floor(total * 0.10) + ' puntos';
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
