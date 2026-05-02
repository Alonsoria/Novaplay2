<?php
/**
 * NOVAPLAY — COMBOS.PHP
 * Página de paquetes especiales (combos).
 */

$pageTitle = 'Combos';
require_once 'header.php';

/* ── Cargar combos activos con sus productos relacionados ── */
$combos = [];
$result = $conn->query("SELECT * FROM combos WHERE activo = 1 ORDER BY id_combo ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        /* Productos incluidos en el combo */
        $idCombo = (int)$row['id_combo'];
        $subRes  = $conn->query(
            "SELECT p.nombre, p.precio
             FROM combo_relacion cr
             JOIN productos p ON cr.id_producto = p.id_producto
             WHERE cr.id_combo = $idCombo"
        );
        $row['productos'] = [];
        if ($subRes) {
            while ($prod = $subRes->fetch_assoc()) {
                $row['productos'][] = $prod;
            }
        }
        $combos[] = $row;
    }
}
?>

<main class="combos-page" aria-label="Combos especiales">

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
    <div>
      <h2 style="margin-bottom:var(--space-xs);">
        <i class="fa-solid fa-layer-group" aria-hidden="true" style="color:var(--clr-warning);margin-right:8px;"></i>
        Combos Especiales
      </h2>
      <p class="text-muted" style="font-size:.9rem;">Paquetes exclusivos con el mejor precio del mercado.</p>
    </div>
    <span class="text-muted" style="font-size:.9rem;">
      <?= count($combos) ?> combo<?= count($combos) !== 1 ? 's' : '' ?> disponible<?= count($combos) !== 1 ? 's' : '' ?>
    </span>
  </div>

  <?php if (empty($combos)): ?>

    <div class="cart-empty">
      <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
      <p>No hay combos disponibles en este momento.</p>
      <a href="productos.php" class="btn mt-md">
        <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
        Ver catálogo completo
      </a>
    </div>

  <?php else: ?>

    <div class="combo-grid">
      <?php foreach ($combos as $combo): ?>
        <article class="combo-card">

          <!-- Banner del combo -->
          <div class="combo-card__banner">
            <?php if (!empty($combo['imagen'])): ?>
              <img src="<?= e($combo['imagen']) ?>"
                   alt="<?= e($combo['nombre']) ?>"
                   loading="lazy">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--clr-surface-2);">
                <i class="fa-solid fa-layer-group" style="font-size:3rem;color:var(--clr-border);"></i>
              </div>
            <?php endif; ?>
          </div>

          <!-- Cuerpo -->
          <div class="combo-card__body">
            <span class="combo-badge">COMBO</span>

            <h3 style="color:var(--clr-white);font-size:1rem;line-height:1.3;">
              <?= e($combo['nombre']) ?>
            </h3>

            <?php if (!empty($combo['descripcion'])): ?>
              <p class="text-muted" style="font-size:.85rem;line-height:1.5;">
                <?= e($combo['descripcion']) ?>
              </p>
            <?php endif; ?>

            <!-- Productos incluidos (si están relacionados) -->
            <?php if (!empty($combo['productos'])): ?>
              <div style="border-top:1px solid var(--clr-border);padding-top:var(--space-sm);">
                <p style="font-size:.75rem;color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Incluye:</p>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:4px;">
                  <?php foreach ($combo['productos'] as $prod): ?>
                    <li style="font-size:.82rem;color:var(--clr-text);">
                      <i class="fa-solid fa-check" style="color:var(--clr-success);margin-right:4px;" aria-hidden="true"></i>
                      <?= e($prod['nombre']) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Precio -->
            <div style="margin-top:auto;padding-top:var(--space-sm);">
              <span class="combo-price-new">$<?= number_format((float)$combo['precio'], 2) ?></span>
            </div>

            <!-- Acción -->
            <?php if (is_logged_in()): ?>
              <button class="btn btn-add-combo mt-sm"
                      data-combo="<?= (int)$combo['id_combo'] ?>"
                      data-name="<?= e($combo['nombre']) ?>"
                      style="width:100%;">
                <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
                Añadir al carrito
              </button>
            <?php else: ?>
              <a href="login.php" class="btn mt-sm" style="width:100%;text-align:center;">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                Iniciar sesión para comprar
              </a>
            <?php endif; ?>
          </div>

        </article>
      <?php endforeach; ?>
    </div>

    <!-- Aviso informativo -->
    <div style="margin-top:var(--space-xl);padding:var(--space-lg);background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);display:flex;gap:var(--space-md);align-items:flex-start;">
      <i class="fa-solid fa-circle-info" style="color:var(--clr-accent);font-size:1.4rem;flex-shrink:0;margin-top:2px;" aria-hidden="true"></i>
      <div>
        <strong style="color:var(--clr-white);">¿Qué incluye cada combo?</strong>
        <p class="text-muted" style="margin-top:4px;font-size:.875rem;">
          Los combos son paquetes de productos seleccionados a un precio especial. Al añadir un combo al carrito se agregan automáticamente todos los productos incluidos.
        </p>
      </div>
    </div>

  <?php endif; ?>

</main>

<!-- Modal login -->
<div class="modal" id="loginModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="lmCombosTitle">
  <div class="modal-content">
    <button class="modal-close" id="loginModalClose" aria-label="Cerrar">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h3 id="lmCombosTitle">Inicia sesión para comprar</h3>
    <p>Necesitas una cuenta para añadir combos al carrito.</p>
    <div class="modal-actions">
      <a href="login.php" class="btn-login">Iniciar sesión</a>
      <a href="signup.php" class="btn-login" style="background:var(--clr-surface-2);border:1px solid var(--clr-border);">Registrarse</a>
    </div>
  </div>
</div>

<script>
(function () {
  const loginModal = document.getElementById('loginModal');
  const closeBtn   = document.getElementById('loginModalClose');

  if (closeBtn) closeBtn.addEventListener('click', function () { loginModal.style.display = 'none'; });
  if (loginModal) loginModal.addEventListener('click', function (e) {
    if (e.target === loginModal) loginModal.style.display = 'none';
  });

  document.querySelectorAll('.btn-add-combo').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const comboId = btn.dataset.combo;
      btn.disabled  = true;

      fetch('agregar_combo.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'combo_id=' + encodeURIComponent(comboId),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.error) { alert(data.error); btn.disabled = false; return; }
        const badge = document.getElementById('cartBadge');
        if (badge && data.cartCount !== undefined) {
          badge.textContent = data.cartCount;
          badge.classList.remove('d-none');
        }
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Añadido';
        setTimeout(function () {
          btn.disabled  = false;
          btn.innerHTML = '<i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Añadir al carrito';
        }, 2000);
      })
      .catch(function () {
        btn.disabled = false;
        alert('Error al añadir el combo. Intenta de nuevo.');
      });
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
