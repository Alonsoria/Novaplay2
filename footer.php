<?php
/**
 * NOVAPLAY — FOOTER.PHP
 * Pie de página global + scripts JS compartidos.
 * Incluir al FINAL de cada página: require 'footer.php';
 */
?>

</div><!-- /.page-wrapper -->

<!-- ═══════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════ -->
<footer class="footer">
  <div class="footer-container">
        <p class="footer-note">
      &copy; <?= date('Y') ?> Novaplay - E-commerce de Videojuegos
    </p>
    <div class="footer-links">
      <a href="aviso_privacidad.php">Aviso de Privacidad</a>
      <a href="terminos.php">Términos y Condiciones</a>
      <a href="cookies.php">Política de Cookies</a>
      <a href="about.php">Acerca de NovaPlay</a>
    </div>
    <p class="footer-note">
      La información proporcionada será tratada conforme a nuestro Aviso de Privacidad.
    </p>
  </div>
</footer>

<!-- ═══════════════════════════════════════════════
     SCRIPTS GLOBALES
════════════════════════════════════════════════ -->
<!-- AOS — Animate On Scroll -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<!-- Vanilla Tilt — efecto 3D tilt en cards -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js"></script>

<script>
/* ─── Sidebar off-canvas (mobile) ─── */
(function () {
  const burger    = document.getElementById('burgerBtn');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebarOverlay');
  const closeBtn  = document.getElementById('sidebarCloseBtn');
  if (!burger || !sidebar) return;

  function openSidebar() {
    sidebar.classList.add('open');
    if (overlay)  { overlay.classList.add('open');  overlay.setAttribute('aria-hidden', 'false'); }
    burger.classList.add('open');
    burger.setAttribute('aria-expanded', 'true');
    burger.setAttribute('aria-label', 'Cerrar menú');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    if (overlay)  { overlay.classList.remove('open'); overlay.setAttribute('aria-hidden', 'true'); }
    burger.classList.remove('open');
    burger.setAttribute('aria-expanded', 'false');
    burger.setAttribute('aria-label', 'Abrir menú');
    document.body.style.overflow = '';
  }

  burger.addEventListener('click', function (e) {
    e.stopPropagation();
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (overlay)  overlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });
})();

/* ─── Dropdown plataformas ─── */
(function () {
  const toggleBtn = document.getElementById('platformToggleBtn');
  const menu      = document.getElementById('platformMenu');
  const closeBtn  = document.getElementById('platformCloseBtn');
  if (!toggleBtn || !menu) return;

  toggleBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    const open = menu.classList.toggle('open');
    toggleBtn.setAttribute('aria-expanded', open);
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('click', function (e) {
    if (!document.getElementById('platformDropdown').contains(e.target)) {
      menu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  });
})();

/* ─── Búsqueda en tiempo real (opcional, sin romper nada) ─── */
(function () {
  const searchInput = document.getElementById('search-input');
  if (!searchInput) return;
  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      const q = searchInput.value.trim();
      if (q.length > 0) {
        window.location.href = 'productos.php?q=' + encodeURIComponent(q);
      }
    }
  });
})();

/* ─── CARD-STACK — Transición suave entre tarjetas de suscripción ─── */
(function () {
  document.querySelectorAll('.card-stack').forEach(function (stack) {
    let animating = false;

    stack.addEventListener('click', function () {
      if (animating) return;
      const cards = Array.from(stack.querySelectorAll('.sub-card'));
      if (cards.length < 2) return;

      animating = true;
      const top = cards[cards.length - 1];

      top.classList.add('moving-back');

      top.addEventListener('animationend', function () {
        top.classList.remove('moving-back');

        /* Activar transición en todas las cartas para el reordenado */
        cards.forEach(c => c.classList.add('stack-smooth'));

        /* Mover al fondo del DOM */
        stack.insertBefore(top, cards[0]);

        /* Limpiar clase de transición tras completar el movimiento */
        const dur = parseFloat(getComputedStyle(top).transitionDuration) * 1000 || 300;
        setTimeout(function () {
          cards.forEach(c => c.classList.remove('stack-smooth'));
          animating = false;
        }, dur + 50);

      }, { once: true });
    });
  });
})();

/* ─── Panel de notificaciones (sidebar) ─── */
(function () {
  const btn   = document.getElementById('notifBtn');
  const panel = document.getElementById('notifPanel');
  const close = document.getElementById('notifClose');
  if (!btn || !panel) return;

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    const open = panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
    panel.setAttribute('aria-hidden', !open);
  });

  if (close) {
    close.addEventListener('click', function () {
      panel.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      panel.setAttribute('aria-hidden', 'true');
    });
  }

  document.addEventListener('click', function (e) {
    if (!panel.contains(e.target) && e.target !== btn) {
      panel.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      panel.setAttribute('aria-hidden', 'true');
    }
  });
})();

/* ─── Ver detalle en panel de notificaciones ─── */
(function () {
  /* Toggle de la sección de códigos */
  document.querySelectorAll('.notif-detalle-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation(); /* evita que cierre el panel */
      const pedId  = btn.dataset.pedido;
      const det    = document.getElementById('notif-det-' + pedId);
      if (!det) return;
      const open = det.style.display === 'none';
      det.style.display = open ? 'block' : 'none';
      btn.setAttribute('aria-expanded', open);
      btn.innerHTML = open
        ? '<i class="fa-solid fa-chevron-up" aria-hidden="true"></i> Cerrar'
        : '<i class="fa-solid fa-eye" aria-hidden="true"></i> Ver';
    });
  });

  /* Copiar código individual en el panel */
  document.querySelectorAll('.notif-copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const raw = btn.dataset.raw;
      navigator.clipboard.writeText(raw).then(function () {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
        btn.style.color = 'var(--clr-success)';
        setTimeout(function () { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
      });
    });
  });
})();

/* ─── Splash en iconos del sidebar ─── */
(function () {
  document.querySelectorAll('.sidebar-icon').forEach(function (icon) {
    icon.addEventListener('click', function (e) {
      const rect   = icon.getBoundingClientRect();
      const cx     = e.clientX - rect.left;
      const cy     = e.clientY - rect.top;
      const drops  = [
        { x: -18, y: -12 }, { x: 14, y: -18 },
        { x: -10, y: 16  }, { x: 18, y: 10  }
      ];
      drops.forEach(function (d) {
        const el = document.createElement('span');
        el.className = 'splash-drop';
        el.style.setProperty('--x', d.x + 'px');
        el.style.setProperty('--y', d.y + 'px');
        el.style.left = cx + 'px';
        el.style.top  = cy + 'px';
        icon.appendChild(el);
        el.addEventListener('animationend', function () { el.remove(); });
      });
    });
  });
})();

/* ─── AOS — añadir atributos data-aos dinámicamente ─── */
(function () {
  if (typeof AOS === 'undefined') return;

  document.querySelectorAll('.card').forEach(function (el, i) {
    if (!el.hasAttribute('data-aos')) {
      el.setAttribute('data-aos', 'fade-up');
      el.setAttribute('data-aos-delay', String((i % 5) * 70));
      el.setAttribute('data-aos-duration', '550');
    }
  });

  document.querySelectorAll('.combo-card').forEach(function (el, i) {
    if (!el.hasAttribute('data-aos')) {
      el.setAttribute('data-aos', 'zoom-in');
      el.setAttribute('data-aos-delay', String(i * 90));
    }
  });

  document.querySelectorAll('.reward-day').forEach(function (el, i) {
    if (!el.hasAttribute('data-aos')) {
      el.setAttribute('data-aos', 'flip-left');
      el.setAttribute('data-aos-delay', String(i * 55));
      el.setAttribute('data-aos-duration', '500');
    }
  });

  document.querySelectorAll('.team-card').forEach(function (el, i) {
    if (!el.hasAttribute('data-aos')) {
      el.setAttribute('data-aos', 'fade-up');
      el.setAttribute('data-aos-delay', String(i * 100));
    }
  });

  document.querySelectorAll('main > h2, .sub-title').forEach(function (el) {
    if (!el.hasAttribute('data-aos')) {
      el.setAttribute('data-aos', 'fade-right');
      el.setAttribute('data-aos-duration', '600');
    }
  });

  AOS.init({
    duration: 600,
    once: true,
    offset: 40,
    easing: 'ease-out-cubic'
  });
})();

/* ─── Vanilla Tilt — efecto 3D en cards ─── */
(function () {
  if (typeof VanillaTilt === 'undefined') return;

  var isMobile = window.matchMedia('(max-width: 768px)').matches;
  if (isMobile) return;

  VanillaTilt.init(document.querySelectorAll('.card'), {
    max: 8,
    speed: 400,
    glare: true,
    'max-glare': 0.12,
    scale: 1.03,
    gyroscope: false
  });

  VanillaTilt.init(document.querySelectorAll('.combo-card'), {
    max: 5,
    speed: 400,
    glare: true,
    'max-glare': 0.08,
    scale: 1.02,
    gyroscope: false
  });
})();

/* ─── Sparkles — efecto de partículas neon en botones ─── */
(function () {
  var colors = ['#00f5ff', '#ff2d78', '#a855f7', '#39ff14', '#ffe600'];

  document.querySelectorAll('.btn, .btn2, .btn-sub').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var rect = btn.getBoundingClientRect();
      var cx = e.clientX - rect.left;
      var cy = e.clientY - rect.top;

      for (var i = 0; i < 8; i++) {
        var spark = document.createElement('span');
        spark.className = 'nv-spark';
        var angle = (i / 8) * Math.PI * 2;
        var dist  = 28 + Math.random() * 20;
        spark.style.setProperty('--sx', Math.cos(angle) * dist + 'px');
        spark.style.setProperty('--sy', Math.sin(angle) * dist + 'px');
        spark.style.left = cx + 'px';
        spark.style.top  = cy + 'px';
        spark.style.background = colors[Math.floor(Math.random() * colors.length)];
        btn.appendChild(spark);
        spark.addEventListener('animationend', function () { spark.remove(); });
      }
    });
  });
})();
</script>
</body>
</html>
