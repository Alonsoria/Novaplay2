<?php
/**
 * NOVAPLAY — INDEX.PHP
 * Página principal: Hero + Suscripciones + Catálogo destacado.
 */

$pageTitle = 'Inicio';
require_once 'header.php';

/* ── Productos destacados (query original sin modificar) ── */
$juegos = [];
$result = $conn->query("SELECT * FROM productos LIMIT 8");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $juegos[] = $row;
    }
}

/* ── Datos hero (slides) ── */
$heroSlides = [
    [
        'title'     => 'The Legend of Zelda: Tears of the Kingdom',
        'desc'      => 'Explora los cielos de Hyrule en la aventura más épica de Link.',
        'img'       => './images/TOTKlogo.png',
        'character' => './images/zelda-char.png',
        'rating'    => 5,
        'date'      => 'Disponible ahora',
        'link'      => 'productos.php?plataforma=Nintendo',
    ],
    [
        'title'     => 'Friday Night Funkin\'',
        'desc'      => 'El juego de ritmo indie que conquistó internet. ¡Demuestra tu flow!',
        'img'       => './images/fnf-char.png',
        'character' => './images/BfPng2k.png',
        'rating'    => 4,
        'date'      => 'Disponible ahora',
        'link'      => 'productos.php',
    ],
    [
        'title'     => 'Halo Infinite',
        'desc'      => 'El Jefe Maestro regresa. La leyenda continúa en el universo expandido de Halo.',
        'img'       => './images/HaloLogo.png',
        'character' => './images/HaloPng.png',
        'rating'    => 5,
        'date'      => 'Disponible ahora',
        'link'      => 'productos.php?plataforma=Xbox',
    ],
];

/* ── Datos suscripciones ── */
$subs = [
    [
        'class'    => 'xbox',
        'badge'    => 'Xbox',
        'plans'    => [
            ['name' => 'Xbox Game Pass Essential',     'price' => '$129',  'period' => '/mes',  'features' => ['Más de 50 juegos en la consola Xbox, PC y dispositivos compatibles', 'Juegos multijugador en línea para consola', 'Beneficios para juegos como League of Legends y Call of Duty: Warzone']],
            ['name' => 'Xbox Game Pass Premium',     'price' => '$179',  'period' => '/mes',  'features' => ['Más de 200 juegos en la consola Xbox, PC y dispositivos compatibles', 'Beneficios para juegos como League of Legends y Call of Duty: Warzone', 'Juegos multijugador en línea para consola']],
            ['name' => 'Xbox Game Pass Ultimate', 'price' => '$289', 'period' => '/mes',  'features' => ['Más de 400 juegos en la consola Xbox, PC y dispositivos compatibles','Nuevos juegos desde el mismo día de su lanzamiento','Incluye Club de Fortnite, EA Play y Ubisoft+ Classics', 'Juegos multijugador en línea para consola', 'Cloud gaming en móvil y tablet']]
        ],
        'btn_text' => 'Ver planes Xbox',
        'btn_id'   => 'btnXbox',
    ],
    [
        'class'    => 'playstation',
        'badge'    => 'PlayStation',
        'plans'    => [
            ['name' => 'PS Plus Essential', 'price' => '$119',  'period' => '/mes', 'features' => ['Juegos mensuales: puedes descargar y jugar títulos nuevos cada mes mientras tengas la suscripción.', 'Multijugador online: acceso para jugar en línea con otros jugadores.', 'Descuentos exclusivos: ofertas especiales en la tienda de PlayStation.']],
            ['name' => 'PS Plus Extra',     'price' => '$179', 'period' => '/mes', 'features' => ['Todo lo de Essential', 'Catálogo de cientos de juegos (PS4 y PS5)', 'Juegos de Ubisoft+']],
            ['name' => 'PS Plus Deluxe',   'price' => '$209', 'period' => '/mes', 'features' => ['Todo lo de Extra', 'Catálogo clásico (PS1/PS2/PSP)', 'Streaming de juegos', 'Pruebas de tiempo limitado']],
        ],
        'btn_text' => 'Ver planes PS Plus',
        'btn_id'   => 'btnPS',
    ],
    [
        'class'    => 'nintendo',
        'badge'    => 'Nintendo',
        'plans'    => [
            ['name' => 'Nintendo Switch Online ', 'price' => '$309',  'period' => '/año', 'features' => ['Multijugador online', 'NES + SNES clásicos', 'Almacenamiento en la nube', 'Ofertas exclusivas']],
            ['name' => 'Nintendo Switch Online + Paquete Expansión', 'price' => '$859', 'period' => '/año', 'features' => ['Todo lo del plan básico', 'Más juegos clásicos (N64 + Genesis + GBA + GBC)', 'DLC gratuito de juegos']]
        ],
        'btn_text' => 'Ver planes Nintendo',
        'btn_id'   => 'btnNintendo',
    ],
];
?>

<!-- ══════════════ HERO ══════════════ -->
<section class="hero" id="heroSection" aria-label="Juegos destacados">

  <!-- Fondo difuso (splash decorativo) -->
  <img src="./images/splash1.png" alt="" class="splash splash-1" aria-hidden="true">
  <img src="./images/splash2.png" alt="" class="splash splash-2" aria-hidden="true">

  <!-- Fondo del slide actual -->
  <img src="<?= e($heroSlides[0]['img']) ?>"
       alt=""
       class="hero-bg-image"
       id="heroBgImg"
       aria-hidden="true">

  <!-- Contenido textual -->
  <div class="contenidoJuego" id="heroContent">
    <div class="rating-container">
      <div class="stars" id="heroStars" aria-label="Calificación">
        <?php for ($i = 0; $i < 5; $i++): ?>
          <i class="fa-star <?= $i < $heroSlides[0]['rating'] ? 'fa-solid' : 'fa-regular' ?>" aria-hidden="true"></i>
        <?php endfor; ?>
      </div>
    </div>
    <div class="release-date">
      <span class="date-text" id="heroDate"><?= e($heroSlides[0]['date']) ?></span>
    </div>
    <h1 class="TituloHero" id="heroTitle"><?= e($heroSlides[0]['title']) ?></h1>
    <p class="parrafoHero" id="heroDesc"><?= e($heroSlides[0]['desc']) ?></p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="<?= e($heroSlides[0]['link']) ?>" class="btn" id="heroLink">
        <i class="fa-solid fa-shopping-cart" aria-hidden="true"></i>
        Comprar ahora
      </a>
      <a href="productos.php" class="btn2">
        Ver catálogo
      </a>
    </div>
  </div>

  <!-- Personaje decorativo -->
  <img src="<?= e($heroSlides[0]['character']) ?>"
       alt=""
       class="hero-character"
       id="heroCharacter"
       aria-hidden="true">

  <!-- Indicadores de slides -->
  <div class="hero-indicators" aria-label="Slides del hero" style="position:absolute;bottom:24px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:5;">
    <?php foreach ($heroSlides as $i => $slide): ?>
      <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>"
              aria-label="Ir al slide <?= $i+1 ?>"
              data-index="<?= $i ?>"
              style="width:<?= $i===0?'28px':'10px' ?>;height:10px;border-radius:999px;border:none;background:<?= $i===0?'var(--clr-accent)':'rgba(255,255,255,.3)' ?>;cursor:pointer;transition:all .3s ease;"></button>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══════════════ SUSCRIPCIONES ══════════════ -->
<section class="subscriptions" aria-label="Planes de suscripción">
  <div style="padding: 0 var(--space-xl);">
    <h2 class="sub-title">Suscripciones Gaming</h2>
    <div class="sub-container" id="subContainer">

      <?php foreach ($subs as $sub): ?>
      <div class="card-stack" aria-label="Planes <?= e($sub['badge']) ?>">
        <?php foreach (array_reverse($sub['plans']) as $plan): ?>
        <div class="sub-card <?= e($sub['class']) ?>">
          <span class="badge"><?= e($sub['badge']) ?></span>
          <h3><?= e($plan['name']) ?></h3>
          <p class="price">
            <?= e($plan['price']) ?>
            <span><?= e($plan['period']) ?></span>
          </p>
          <ul class="features">
            <?php foreach ($plan['features'] as $feat): ?>
              <li>
                <i class="fa-solid fa-check" aria-hidden="true"></i>
                <?= e($feat) ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <button class="btn-sub <?= e($sub['btn_id']) ?>" data-plan="<?= e($plan['name']) ?>">
            <?= e($sub['btn_text']) ?>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- ══════════════ CATÁLOGO DESTACADO ══════════════ -->
<main aria-label="Catálogo de juegos">
  <h2>Juegos Destacados</h2>
  <div class="grid">
    <?php if (empty($juegos)): ?>
      <p class="text-muted">No hay productos disponibles en este momento.</p>
    <?php else: ?>
      <?php foreach ($juegos as $juego): ?>
        <article class="card">
          <?php if (!empty($juego['imagen'])): ?>
            <img src="<?= e($juego['imagen']) ?>"
                 alt="<?= e($juego['nombre']) ?>"
                 class="product-img"
                 loading="lazy">
          <?php else: ?>
            <div class="product-img" style="display:flex;align-items:center;justify-content:center;background:var(--clr-surface-2);">
              <i class="fa-solid fa-gamepad" style="font-size:2rem;color:var(--clr-border);"></i>
            </div>
          <?php endif; ?>

          <h3><?= e($juego['nombre']) ?></h3>
          <p><?= e(mb_substr($juego['descripcion'] ?? '', 0, 80)) ?>…</p>
          <strong>$<?= number_format((float)($juego['precio'] ?? 0), 2) ?></strong>

          <?php if (!empty($juego['plataformas'])): ?>
            <div class="plat-list">
              <?php
              $plats = explode(',', $juego['plataformas']);
              $platImgs = [
                'PlayStation' => 'img/ps.png',
                'Xbox'        => 'img/xbox.png',
                'Nintendo'    => 'img/nintendo.png',
                'PC'          => 'img/pc.png',
              ];
              foreach ($plats as $plat):
                $plat = trim($plat);
                $imgSrc = $platImgs[$plat] ?? null;
              ?>
                <?php if ($imgSrc): ?>
                  <img src="<?= e($imgSrc) ?>"
                       alt="<?= e($plat) ?>"
                       class="plat-thumb"
                       title="<?= e($plat) ?>">
                <?php else: ?>
                  <small class="text-muted"><?= e($plat) ?></small>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <button class="btn btn-add-cart mt-sm"
                  data-id="<?= (int)$juego['id_producto'] ?>"
                  data-name="<?= e($juego['nombre']) ?>"
                  style="margin-top:auto;">
            <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
            Añadir
          </button>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="text-center" style="margin-bottom:var(--space-2xl);">
    <a href="productos.php" class="btn2">Ver todo el catálogo</a>
  </div>
</main>

<!-- Modal: requiere login para comprar -->
<div class="modal" id="loginModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
  <div class="modal-content">
    <button class="modal-close" id="loginModalClose" aria-label="Cerrar">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <h3 id="loginModalTitle">Inicia sesión para comprar</h3>
    <p>Necesitas una cuenta para añadir productos al carrito.</p>
    <div class="modal-actions">
      <a href="login.php" class="btn-login">Iniciar sesión</a>
      <a href="signup.php" class="btn-login" style="background:var(--clr-surface-2);border:1px solid var(--clr-border);">Registrarse</a>
    </div>
  </div>
</div>

<script>
/* ──────────────────────────────────────────────
   HERO — Rotación de slides
────────────────────────────────────────────── */
(function () {
  const slides = <?= json_encode($heroSlides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  let current  = 0;
  let timer;

  const titleEl  = document.getElementById('heroTitle');
  const descEl   = document.getElementById('heroDesc');
  const dateEl   = document.getElementById('heroDate');
  const linkEl   = document.getElementById('heroLink');
  const charEl   = document.getElementById('heroCharacter');
  const bgEl     = document.getElementById('heroBgImg');
  const starsEl  = document.getElementById('heroStars');
  const dots     = document.querySelectorAll('.hero-dot');

  function setSlide(idx) {
    const s = slides[idx];
    [titleEl, descEl, dateEl, charEl, bgEl].forEach(el => { if (el) el.style.opacity = '0'; });

    setTimeout(function () {
      if (titleEl)  titleEl.textContent  = s.title;
      if (descEl)   descEl.textContent   = s.desc;
      if (dateEl)   dateEl.textContent   = s.date;
      if (linkEl)   linkEl.href          = s.link;
      if (charEl)   charEl.src           = s.character;
      if (bgEl)     bgEl.src             = s.img;

      /* Estrellas */
      if (starsEl) {
        starsEl.innerHTML = '';
        for (let i = 0; i < 5; i++) {
          const star = document.createElement('i');
          star.className = 'fa-star ' + (i < s.rating ? 'fa-solid' : 'fa-regular');
          star.setAttribute('aria-hidden', 'true');
          starsEl.appendChild(star);
        }
      }

      [titleEl, descEl, dateEl, charEl, bgEl].forEach(el => { if (el) el.style.opacity = '1'; });

      /* Dots */
      dots.forEach(function (dot, i) {
        const isActive = i === idx;
        dot.style.width      = isActive ? '28px' : '10px';
        dot.style.background = isActive ? 'var(--clr-accent)' : 'rgba(255,255,255,.3)';
        dot.classList.toggle('active', isActive);
      });
    }, 300);
  }

  function nextSlide() {
    current = (current + 1) % slides.length;
    setSlide(current);
  }

  function startTimer() {
    timer = setInterval(nextSlide, 6000);
  }

  dots.forEach(function (dot, i) {
    dot.addEventListener('click', function () {
      clearInterval(timer);
      current = i;
      setSlide(current);
      startTimer();
    });
  });

  /* Transiciones de opacidad en CSS */
  [titleEl, descEl, dateEl, charEl, bgEl].forEach(function (el) {
    if (el) el.style.transition = 'opacity 0.3s ease';
  });

  startTimer();
})();

/* ──────────────────────────────────────────────
   CARD-STACK — Rotar tarjetas al click
────────────────────────────────────────────── */
(function () {
  document.querySelectorAll('.card-stack').forEach(function (stack) {
    stack.addEventListener('click', function () {
      const cards = stack.querySelectorAll('.sub-card');
      if (cards.length < 2) return;
      const top = cards[cards.length - 1];
      top.classList.add('moving-back');
      top.addEventListener('animationend', function () {
        top.classList.remove('moving-back');
        stack.insertBefore(top, cards[0]);
      }, { once: true });
    });
  });
})();

/* ──────────────────────────────────────────────
   CARRITO — Añadir producto via AJAX
────────────────────────────────────────────── */
(function () {
  const isLoggedIn = <?= is_logged_in() ? 'true' : 'false' ?>;
  const loginModal = document.getElementById('loginModal');
  const loginModalClose = document.getElementById('loginModalClose');

  if (loginModal && loginModalClose) {
    loginModalClose.addEventListener('click', function () {
      loginModal.style.display = 'none';
    });
    loginModal.addEventListener('click', function (e) {
      if (e.target === loginModal) loginModal.style.display = 'none';
    });
  }

  document.querySelectorAll('.btn-add-cart').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!isLoggedIn) {
        if (loginModal) loginModal.style.display = 'flex';
        return;
      }

      const id   = btn.dataset.id;
      const name = btn.dataset.name;
      btn.disabled = true;

      fetch('agregar_carrito.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'producto_id=' + encodeURIComponent(id) +
              '&csrf_token=' + encodeURIComponent(
                document.cookie.match(/PHPSESSID=([^;]+)/)?.[1] ?? ''
              ),
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        /* Actualizar badge */
        const badge = document.getElementById('cartBadge');
        if (badge && data.cartCount !== undefined) {
          badge.textContent = data.cartCount;
          badge.classList.remove('d-none');
        }
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Añadido';
        setTimeout(function () {
          btn.disabled    = false;
          btn.innerHTML   = '<i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Añadir';
        }, 1800);
      })
      .catch(function () {
        btn.disabled = false;
        alert('Error al añadir al carrito. Intenta de nuevo.');
      });
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
