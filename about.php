<?php
$pageTitle = 'Acerca de NovaPlay';
require_once 'header.php';
?>

<div class="about-page">
  <div class="about-hero">
    <h1>Acerca de NovaPlay</h1>
    <p>Somos una plataforma gamer creada por y para jugadores. Nuestra misión es hacer el gaming más accesible con los mejores precios en juegos y suscripciones.</p>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-lg);margin-top:var(--space-xl);">
    <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:var(--space-xl);text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:var(--space-md);color:var(--clr-accent);"><i class="fa-solid fa-gamepad" aria-hidden="true"></i></div>
      <h3 class="about-feat-title" style="color:var(--clr-white);margin-bottom:var(--space-sm);">+500 Juegos</h3>
      <p class="text-muted" style="font-size:.9rem;">Catálogo en constante crecimiento para todas las plataformas.</p>
    </div>
    <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:var(--space-xl);text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:var(--space-md);color:var(--clr-accent);"><i class="fa-solid fa-lock" aria-hidden="true"></i></div>
      <h3 class="about-feat-title" style="color:var(--clr-white);margin-bottom:var(--space-sm);">Pago Seguro</h3>
      <p class="text-muted" style="font-size:.9rem;">Tus datos protegidos con cifrado de nivel bancario.</p>
    </div>
    <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:var(--space-xl);text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:var(--space-md);color:var(--clr-warning);"><i class="fa-solid fa-star" aria-hidden="true"></i></div>
      <h3 class="about-feat-title" style="color:var(--clr-white);margin-bottom:var(--space-sm);">Recompensas</h3>
      <p class="text-muted" style="font-size:.9rem;">Acumula puntos diarios y canjéalos en tus compras.</p>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
