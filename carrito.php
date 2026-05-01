<?php
/**
 * NOVAPLAY — CARRITO.PHP
 * Vista del carrito de compras. Requiere sesión.
 */

$pageTitle = 'Carrito';
require_once 'header.php';
require_login();

$uid = (int)$_SESSION['user_id'];

/* ── Cargar items del carrito con nombres de columna correctos ── */
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

$total = array_reduce($items, function ($carry, $item) {
    return $carry + ($item['precio'] * $item['cantidad']);
}, 0.0);
?>

<main class="cart-page" aria-label="Carrito de compras">

  <h2><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Mi Carrito</h2>

  <?php if (empty($items)): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
      <p>Tu carrito está vacío.</p>
      <a href="productos.php" class="btn mt-md">Ver juegos</a>
    </div>

  <?php else: ?>

    <!-- Tabla de productos -->
    <div style="overflow-x:auto;">
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
                  <button aria-label="Disminuir cantidad"
                          class="qty-btn"
                          data-action="decrease"
                          data-id="<?= (int)$item['id'] ?>">−</button>
                  <span id="qty-<?= (int)$item['id'] ?>"><?= (int)$item['cantidad'] ?></span>
                  <button aria-label="Aumentar cantidad"
                          class="qty-btn"
                          data-action="increase"
                          data-id="<?= (int)$item['id'] ?>">+</button>
                </div>
              </td>
              <td id="subtotal-<?= (int)$item['id'] ?>">
                $<?= number_format((float)$item['precio'] * (int)$item['cantidad'], 2) ?>
              </td>
              <td>
                <button class="btn btn--danger" style="padding:6px 12px;font-size:.8rem;"
                        aria-label="Eliminar <?= e($item['nombre']) ?>"
                        data-action="remove"
                        data-id="<?= (int)$item['id'] ?>">
                  <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Resumen -->
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
        <hr style="border:none;border-top:1px solid var(--clr-border);">
        <div class="cart-summary-row">
          <span class="label" style="font-weight:700;color:var(--clr-white);">Total</span>
          <span class="total" id="cartTotalFinal">$<?= number_format($total, 2) ?></span>
        </div>

        <!-- Métodos de pago -->
        <a href="pago_tarjeta.php" class="btn btn-full" style="margin-top:8px;">
          <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
          Pagar con Tarjeta
        </a>
        <a href="crear_pago.php" class="btn2 btn-full" style="margin-top:8px;">
          <i class="fa-brands fa-paypal" aria-hidden="true"></i>
          Pagar con PayPal
        </a>
      </div>
    </div>

  <?php endif; ?>

</main>

<script>
(function () {
  /* Datos de precios para calcular subtotales en el cliente */
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
  }

  document.querySelectorAll('.qty-btn, [data-action="remove"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id     = btn.dataset.id;
      const action = btn.dataset.action;

      fetch('actualizar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id) + '&action=' + encodeURIComponent(action),
      })
      .then(function (r) { return r.json(); })
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

        /* Actualizar badge del carrito en el header */
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
})();
</script>

<?php require_once 'footer.php'; ?>
