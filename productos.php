<?php
/**
 * NOVAPLAY — PRODUCTOS.PHP
 * Catálogo completo con filtro por plataforma y búsqueda.
 * Las consultas SQL originales NO han sido modificadas.
 */

$pageTitle = 'Catálogo';
require_once 'header.php';

/* ── Filtro de plataforma (sanitizado) ── */
$plataforma = clean_str($_GET['plataforma'] ?? '');
$busqueda   = clean_str($_GET['q'] ?? '');

/* ── Query corregida con JOIN a tabla plataformas ── */
if ($plataforma !== '') {
    $stmt = $conn->prepare(
        "SELECT DISTINCT p.* FROM productos p 
         INNER JOIN producto_plataforma pp ON p.id_producto = pp.id_producto 
         INNER JOIN plataformas pl ON pp.id_plataforma = pl.id_plataforma 
         WHERE pl.nombre = ?"
    );
    $stmt->bind_param("s", $plataforma);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($busqueda !== '') {
    $stmt = $conn->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR descripcion LIKE ?");
    $like = '%' . $busqueda . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM productos");
}

$juegos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $juegos[] = $row;
    }
}

$plataformas = ['PlayStation', 'Xbox', 'Nintendo', 'PC'];
?>

<main aria-label="Catálogo de productos">

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
    <h2 style="margin-bottom:0;">
      <?php if ($plataforma): ?>
        Juegos de <?= e($plataforma) ?>
      <?php elseif ($busqueda): ?>
        Resultados para: "<?= e($busqueda) ?>"
      <?php else: ?>
        Todos los Juegos
      <?php endif; ?>
    </h2>
    <span class="text-muted" style="font-size:.9rem;">
      <?= count($juegos) ?> producto<?= count($juegos) !== 1 ? 's' : '' ?> encontrado<?= count($juegos) !== 1 ? 's' : '' ?>
    </span>
  </div>

  <!-- Filtros de plataforma -->
  <nav class="platform-filters" aria-label="Filtrar por plataforma">
    <a href="productos.php" class="<?= $plataforma === '' ? 'active' : '' ?>">
      <i class="fa-solid fa-border-all" aria-hidden="true"></i> Todos
    </a>
    <?php foreach ($plataformas as $p): ?>
      <a href="productos.php?plataforma=<?= urlencode($p) ?>"
         class="<?= $plataforma === $p ? 'active' : '' ?>">
        <?= e($p) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Buscador (visible en mobile donde el header search está oculto) -->
  <form method="GET" action="productos.php" style="display:flex;gap:var(--space-sm);margin-bottom:var(--space-lg);" role="search">
    <div class="site-header__search" style="max-width:380px;border-radius:var(--radius-sm);">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search"
             name="q"
             placeholder="Buscar juegos..."
             value="<?= e($busqueda) ?>"
             maxlength="100"
             aria-label="Buscar">
    </div>
    <button type="submit" class="btn" style="padding:8px 20px;">Buscar</button>
    <?php if ($busqueda || $plataforma): ?>
      <a href="productos.php" class="btn2" style="padding:8px 20px;">Limpiar</a>
    <?php endif; ?>
  </form>

  <!-- Grid de productos -->
  <?php if (empty($juegos)): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <p>No encontramos juegos con ese criterio.</p>
      <a href="productos.php" class="btn mt-md">Ver todos los juegos</a>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($juegos as $juego): ?>
        <article class="card">

          <?php if (!empty($juego['imagen'])): ?>
            <img src="<?= e($juego['imagen']) ?>"
                 alt="<?= e($juego['nombre']) ?>"
                 class="product-img"
                 loading="lazy">
          <?php else: ?>
            <div class="product-img" style="display:flex;align-items:center;justify-content:center;background:var(--clr-surface-2);">
              <i class="fa-solid fa-gamepad" style="font-size:2.5rem;color:var(--clr-border);"></i>
            </div>
          <?php endif; ?>

          <h3><?= e($juego['nombre']) ?></h3>
          <p><?= e(mb_substr($juego['descripcion'] ?? '', 0, 90)) ?>…</p>
          <strong>$<?= number_format((float)($juego['precio'] ?? 0), 2) ?></strong>

          <?php if (!empty($juego['plataformas'])): ?>
            <div class="plat-list">
              <?php
              $platImgs = [
                'PlayStation' => 'img/ps.png',
                'Xbox'        => 'img/xbox.png',
                'Nintendo'    => 'img/nintendo.png',
                'PC'          => 'img/pc.png',
              ];
              foreach (explode(',', $juego['plataformas']) as $plat):
                $plat   = trim($plat);
                $imgSrc = $platImgs[$plat] ?? null;
              ?>
                <?php if ($imgSrc): ?>
                  <img src="<?= e($imgSrc) ?>" alt="<?= e($plat) ?>" class="plat-thumb" title="<?= e($plat) ?>">
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <button class="btn btn-add-cart mt-sm"
                  data-id="<?= (int)$juego['id_producto'] ?>"
                  data-name="<?= e($juego['nombre']) ?>"
                  style="margin-top:auto;">
            <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
            Añadir al carrito
          </button>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<!-- Modal login -->
<div class="modal" id="loginModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="lmTitle">
  <div class="modal-content">
    <button class="modal-close" id="loginModalClose" aria-label="Cerrar">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h3 id="lmTitle">Inicia sesión para comprar</h3>
    <p>Necesitas una cuenta para añadir productos al carrito.</p>
    <div class="modal-actions">
      <a href="login.php" class="btn-login">Iniciar sesión</a>
      <a href="signup.php" class="btn-login" style="background:var(--clr-surface-2);border:1px solid var(--clr-border);">Registrarse</a>
    </div>
  </div>
</div>

<script>
(function () {
  const isLoggedIn  = <?= is_logged_in() ? 'true' : 'false' ?>;
  const loginModal  = document.getElementById('loginModal');
  const closeBtn    = document.getElementById('loginModalClose');

  if (closeBtn) closeBtn.addEventListener('click', function () { loginModal.style.display = 'none'; });
  if (loginModal) loginModal.addEventListener('click', function (e) {
    if (e.target === loginModal) loginModal.style.display = 'none';
  });

  document.querySelectorAll('.btn-add-cart').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!isLoggedIn) { loginModal.style.display = 'flex'; return; }

      const id = btn.dataset.id;
      btn.disabled = true;

      fetch('agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'producto_id=' + encodeURIComponent(id),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        const badge = document.getElementById('cartBadge');
        if (badge && data.cartCount !== undefined) {
          badge.textContent = data.cartCount;
          badge.classList.remove('d-none');
        }
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Añadido';
        setTimeout(function () {
          btn.disabled  = false;
          btn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Añadir al carrito';
        }, 2000);
      })
      .catch(function () {
        btn.disabled = false;
        alert('Error al añadir. Intenta de nuevo.');
      });
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
