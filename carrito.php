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
    "SELECT c.id, c.cantidad, p.id_producto AS producto_id, p.nombre, p.precio, p.imagen,
            COALESCE(p.es_suscripcion, 0) AS es_suscripcion,
            GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ',') AS plataforma_ids
     FROM carrito c
     JOIN  productos p         ON c.id_producto   = p.id_producto
     LEFT JOIN producto_plataforma pp ON p.id_producto   = pp.id_producto
     WHERE c.id_usuario = $uid
     GROUP BY c.id, c.cantidad, p.id_producto, p.nombre, p.precio, p.imagen, p.es_suscripcion"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$total         = array_reduce($items, fn($carry, $item) => $carry + $item['precio'] * $item['cantidad'], 0.0);
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
                  <?php
                  $platImgMap = [
                      1 => ['src' => 'images/plataformas/xboxlogo.png',       'name' => 'Xbox'],
                      2 => ['src' => 'images/plataformas/playstationlogo.png', 'name' => 'PlayStation'],
                      3 => ['src' => 'images/plataformas/steamlogo.png',       'name' => 'Steam'],
                      4 => ['src' => 'images/plataformas/nintendologo.png',    'name' => 'Nintendo'],
                  ];
                  $itemPlats = !empty($item['plataforma_ids'])
                      ? array_map('intval', explode(',', $item['plataforma_ids']))
                      : [];
                  ?>
                  <div class="product-cell">
                    <?php if (!empty($item['imagen'])): ?>
                      <img src="<?= e($item['imagen']) ?>" alt="<?= e($item['nombre']) ?>" class="cart-prod-img">
                    <?php endif; ?>
                    <span>
                      <?= e($item['nombre']) ?>
                      <?php if ($item['es_suscripcion']): ?>
                        <small style="display:block;color:var(--clr-text-muted);font-size:.75rem;margin-top:2px;">
                          Suscripción
                        </small>
                      <?php endif; ?>
                      <?php if (!empty($itemPlats)): ?>
                        <div class="plat-list" style="margin-top:5px;">
                          <?php foreach ($itemPlats as $pid):
                            $pInfo = $platImgMap[$pid] ?? null;
                            if (!$pInfo) continue;
                          ?>
                            <img src="<?= e($pInfo['src']) ?>"
                                 alt="<?= e($pInfo['name']) ?>"
                                 title="<?= e($pInfo['name']) ?>"
                                 class="cart-plat-icon">
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </span>
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
              <span style="font-size:.88rem;font-weight:600;color:var(--clr-text);">
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
                              background:var(--clr-surface-2);color:var(--clr-text);
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
            <span class="label" style="color:var(--clr-success);">
              <i class="fa-solid fa-tag" aria-hidden="true"></i> Descuento puntos
            </span>
            <span style="color:var(--clr-success);" id="cartDescuento">−$<?= number_format($descuentoPts, 2) ?></span>
          </div>
          <?php endif; ?>

          <div class="cart-summary-row">
            <span class="label" style="font-weight:700;color:var(--clr-text);">Total</span>
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

          <!-- Pago en tienda (Conekta Efectivo — requiere POST + CSRF) -->
          <form id="form-pagar-oxxo" method="POST" action="pagar_oxxo.php" style="margin-top:8px;">
            <?= csrf_field() ?>
            <button type="button" id="btn-oxxo"
                    style="width:100%;background:linear-gradient(135deg,#0d9488,#0891b2);
                           border:none;border-radius:var(--radius-full);padding:11px;color:#fff;
                           font-size:.9rem;font-weight:600;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:8px;
                           box-shadow:0 3px 14px rgba(13,148,136,.35);">
              <i class="fa-solid fa-store" aria-hidden="true"></i>
              Pagar en tienda
            </button>
          </form>

          <?php /* TEMPORALMENTE OCULTO — descomentar para restaurar "Pagar con Puntos"
          if ($userPuntos > 0):
            $puntosAlcanzan = $userPuntos >= (int)ceil($totalFinal);
          ?>
          <div style="position:relative;margin-top:8px;" id="pts-pay-wrap">
            <button type="button" id="btn-pagar-puntos"
                    <?= !$puntosAlcanzan ? 'disabled' : '' ?>
                    style="width:100%;background:linear-gradient(135deg,#7c3aed,#4f46e5);
                           border:none;border-radius:var(--radius-sm);padding:11px;color:#fff;
                           font-size:.9rem;font-weight:600;cursor:<?= $puntosAlcanzan ? 'pointer' : 'not-allowed' ?>;
                           opacity:<?= $puntosAlcanzan ? '1' : '.55' ?>;display:flex;
                           align-items:center;justify-content:center;gap:8px;">
              <i class="fa-solid fa-star" aria-hidden="true"></i>
              Pagar con Puntos
              <span style="font-size:.78rem;font-weight:400;opacity:.85;">
                (<?= number_format($userPuntos) ?> disponibles)
              </span>
            </button>
            <?php if (!$puntosAlcanzan): ?>
            <div id="pts-tooltip"
                 style="display:none;position:absolute;bottom:calc(100% + 8px);left:50%;
                        transform:translateX(-50%);background:#1a1a2e;color:var(--clr-text);
                        font-size:.78rem;padding:10px 14px;border-radius:8px;white-space:nowrap;
                        border:1px solid var(--clr-border);box-shadow:0 4px 16px rgba(0,0,0,.4);
                        z-index:100;max-width:280px;white-space:normal;text-align:center;">
              Tus <strong><?= number_format($userPuntos) ?> puntos</strong> ($<?= number_format($userPuntos, 2) ?> MXN)
              no cubren el total de <strong>$<?= number_format($totalFinal, 2) ?></strong>.<br>
              <span style="color:var(--clr-accent-2);">Puedes aplicarlos como descuento parcial
              usando el campo de arriba.</span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif;
          */ ?>

          <!-- Modal de advertencia: Pagar con Puntos -->
          <div id="modal-pagar-puntos"
               role="dialog" aria-modal="true" aria-labelledby="mppts-titulo"
               style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);
                      z-index:10002;align-items:center;justify-content:center;padding:16px;">
            <div style="background:var(--clr-surface);border-radius:16px;padding:28px;
                        max-width:500px;width:100%;max-height:90vh;overflow-y:auto;
                        position:relative;box-shadow:0 0 50px rgba(0,0,0,.6);">
              <button onclick="cerrarModalPuntos()" aria-label="Cerrar"
                      style="position:absolute;top:14px;right:18px;background:none;border:none;
                             color:var(--clr-text-muted);font-size:1.4rem;cursor:pointer;line-height:1;">
                <i class="fa-solid fa-xmark"></i>
              </button>

              <h3 id="mppts-titulo"
                  style="margin-bottom:4px;font-family:var(--font-display);font-size:1.1rem;color:var(--clr-warning);">
                <i class="fa-solid fa-triangle-exclamation" style="margin-right:8px;" aria-hidden="true"></i>
                ¡Atención — Puntos no reembolsables!
              </h3>
              <p style="font-size:.85rem;color:var(--clr-text-muted);margin-bottom:16px;">
                Los puntos utilizados se descontarán <strong style="color:var(--clr-text);">de forma definitiva</strong>
                y <strong style="color:var(--clr-warning);">no podrán ser reembolsados</strong> bajo ninguna circunstancia,
                ni siquiera si solicitas una devolución de productos.
              </p>

              <!-- Productos del carrito con checkboxes -->
              <p style="font-size:.82rem;color:var(--clr-text-muted);margin-bottom:8px;">
                Marca cada producto para confirmar que aceptas las condiciones:
              </p>
              <div id="mppts-items"
                   style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;"></div>

              <form method="POST" action="pagar_con_puntos.php" id="form-pagar-puntos">
                <?= csrf_field() ?>
                <button type="submit" id="mppts-btn-pagar"
                        disabled
                        style="width:100%;background:linear-gradient(135deg,#7c3aed,#4f46e5);
                               border:none;border-radius:8px;padding:11px;color:#fff;
                               font-size:.95rem;font-weight:600;cursor:not-allowed;opacity:.5;
                               display:flex;align-items:center;justify-content:center;gap:8px;">
                  <i class="fa-solid fa-star" aria-hidden="true"></i>
                  Acepto y pago con puntos
                </button>
              </form>
              <p style="font-size:.75rem;color:var(--clr-text-muted);text-align:center;margin-top:8px;">
                Debes marcar todos los productos para habilitar el pago.
              </p>
            </div>
          </div>

        </div>
      </div>

    </div><!-- /.cart-layout -->

  <?php endif; ?>

</main>

<script>
/* ── Datos del carrito para el modal de puntos ── */
const cartItemsData = [
  <?php foreach ($items as $item): ?>
  { id: <?= (int)$item['id'] ?>, nombre: <?= json_encode($item['nombre']) ?>, precio: <?= (float)$item['precio'] ?>, cantidad: <?= (int)$item['cantidad'] ?>, imagen: <?= json_encode($item['imagen'] ?? '') ?> },
  <?php endforeach; ?>
];

/* ── Modal: construir un checkbox por unidad de cada producto ── */
function buildPuntosItems() {
  const container = document.getElementById('mppts-items');
  if (!container) return;
  container.innerHTML = '';
  cartItemsData.forEach(function (item) {
    const liveCant = parseInt((document.getElementById('qty-' + item.id) || {}).textContent, 10) || item.cantidad;
    for (let i = 0; i < liveCant; i++) {
      const label = document.createElement('label');
      label.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;background:var(--clr-surface-2);border-radius:10px;border:1px solid var(--clr-border);cursor:pointer;';
      const cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.style.cssText = 'width:18px;height:18px;cursor:pointer;flex-shrink:0;accent-color:#7c3aed;';
      cb.addEventListener('change', validatePuntosForm);
      const img = document.createElement('img');
      img.src = item.imagen || '';
      img.alt = item.nombre;
      img.style.cssText = 'width:40px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0;';
      img.onerror = function () { this.style.display = 'none'; };
      const info = document.createElement('div');
      info.style.cssText = 'flex:1;font-size:.85rem;color:var(--clr-text);';
      info.textContent = item.nombre;
      if (liveCant > 1) {
        const sm = document.createElement('small');
        sm.style.cssText = 'display:block;color:var(--clr-text-muted);font-size:.75rem;';
        sm.textContent = 'Unidad ' + (i + 1) + ' de ' + liveCant;
        info.appendChild(sm);
      }
      label.appendChild(cb); label.appendChild(img); label.appendChild(info);
      container.appendChild(label);
    }
  });
  validatePuntosForm();
}

function validatePuntosForm() {
  const checks = document.querySelectorAll('#mppts-items input[type="checkbox"]');
  const btn    = document.getElementById('mppts-btn-pagar');
  if (!btn) return;
  const ok = checks.length > 0 && Array.from(checks).every(function (c) { return c.checked; });
  btn.disabled      = !ok;
  btn.style.cursor  = ok ? 'pointer'  : 'not-allowed';
  btn.style.opacity = ok ? '1'        : '.5';
}

function abrirModalPuntos() {
  const m = document.getElementById('modal-pagar-puntos');
  if (!m) return;
  buildPuntosItems();
  m.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function cerrarModalPuntos() {
  const m = document.getElementById('modal-pagar-puntos');
  if (!m) return;
  m.style.display = 'none';
  document.body.style.overflow = '';
}

(function () {
  const precios = {
    <?php foreach ($items as $item): ?>
      <?= (int)$item['id'] ?>: <?= (float)$item['precio'] ?>,
    <?php endforeach; ?>
  };

  const puntosUsados = <?= (int)$puntosUsados ?>;

  function updateTotals() {
    let subtotal = 0;

    document.querySelectorAll('[id^="qty-"]').forEach(function (el) {
      const rowId = el.id.replace('qty-', '');
      const qty   = parseInt(el.textContent, 10) || 0;
      const price = precios[rowId] || 0;
      const linea = price * qty;
      subtotal += linea;

      const subEl = document.getElementById('subtotal-' + rowId);
      if (subEl) subEl.textContent = fmtMXN(linea);
    });

    const descuento  = Math.min(puntosUsados, subtotal);
    const totalFinal = Math.max(0, subtotal - descuento);

    const cartTotalEl = document.getElementById('cartTotal');
    if (cartTotalEl) cartTotalEl.textContent = fmtMXN(subtotal);

    const descEl = document.getElementById('cartDescuento');
    if (descEl) descEl.textContent = '−' + fmtMXN(descuento);

    const totalFinalEl = document.getElementById('cartTotalFinal');
    if (totalFinalEl) totalFinalEl.textContent = fmtMXN(totalFinal);

    const cbEl = document.getElementById('cashbackVal');
    if (cbEl) cbEl.textContent = '+' + Math.floor(totalFinal * 0.10) + ' puntos';
  }

  function updateBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.classList.toggle('d-none', count === 0);
  }

  document.querySelectorAll('.qty-btn, [data-action="remove"]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id     = btn.dataset.id;
      const action = btn.dataset.action;
      btn.disabled = true;

      fetch('actualizar_carrito.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'id=' + encodeURIComponent(id) + '&action=' + encodeURIComponent(action),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (action === 'remove') {
          const row = document.getElementById('row-' + id);
          if (row) row.remove();
          delete precios[id];

          const cartBody = document.getElementById('cartBody');
          if (cartBody && cartBody.querySelectorAll('tr').length === 0) {
            location.reload();
            return;
          }
        } else {
          const qtyEl = document.getElementById('qty-' + id);
          if (qtyEl && data.qty != null) qtyEl.textContent = data.qty;
          btn.disabled = false;
        }

        updateTotals();
        if (data.cartCount != null) updateBadge(data.cartCount);
      })
      .catch(function () { location.reload(); });
    });
  });

  document.querySelectorAll('.pay-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const txt = (document.getElementById('cartTotalFinal') || {}).textContent || '0';
      const num = parseFloat(txt.replace(/[^0-9.,]/g, '').replace(',', ''));
      if (!num || num <= 0) { location.reload(); return; }
      window.location.href = btn.dataset.href;
    });
  });

  /* ── Botón Pagar en OXXO ── */
  var btnOxxo = document.getElementById('btn-oxxo');
  if (btnOxxo) {
    btnOxxo.addEventListener('click', function () {
      var txt = (document.getElementById('cartTotalFinal') || {}).textContent || '0';
      var num = parseFloat(txt.replace(/[^0-9.,]/g, '').replace(',', ''));
      if (!num || num <= 0) { location.reload(); return; }
      btnOxxo.disabled  = true;
      btnOxxo.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando referencia…';
      document.getElementById('form-pagar-oxxo').submit();
    });
  }

  /* ── Tooltip del botón Pagar con Puntos cuando está deshabilitado ── */
  const ptsWrap    = document.getElementById('pts-pay-wrap');
  const ptsTooltip = document.getElementById('pts-tooltip');
  if (ptsWrap && ptsTooltip) {
    ptsWrap.addEventListener('mouseenter', function () { ptsTooltip.style.display = 'block'; });
    ptsWrap.addEventListener('mouseleave', function () { ptsTooltip.style.display = 'none'; });
  }

  /* ── Abrir modal al hacer clic en el botón activo ── */
  const btnPts = document.getElementById('btn-pagar-puntos');
  if (btnPts && !btnPts.disabled) {
    btnPts.addEventListener('click', abrirModalPuntos);
  }

  /* ── Cerrar modal al hacer clic en el backdrop ── */
  const modalPts = document.getElementById('modal-pagar-puntos');
  if (modalPts) {
    modalPts.addEventListener('click', function (e) {
      if (e.target === modalPts) cerrarModalPuntos();
    });
  }
})();
</script>

<?php require_once 'footer.php'; ?>
