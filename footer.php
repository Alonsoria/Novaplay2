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
    <div class="footer-links">
      <a href="aviso_privacidad.php">Aviso de Privacidad</a>
      <a href="terminos.php">Términos y Condiciones</a>
      <a href="cookies.php">Política de Cookies</a>
      <a href="about.php">Acerca de NovaPlay</a>
    </div>
    <p class="footer-note">
      &copy; <?= date('Y') ?> NovaPlay. Todos los derechos reservados.
    </p>
  </div>
</footer>

<!-- ═══════════════════════════════════════════════
     SCRIPTS GLOBALES
════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
/* ─── Menú hamburguesa (mobile) ─── */
(function () {
  const burger   = document.getElementById('burgerBtn');
  const mobileNav = document.getElementById('mobileNav');
  if (!burger || !mobileNav) return;

  burger.addEventListener('click', function () {
    const open = mobileNav.classList.toggle('open');
    burger.setAttribute('aria-expanded', open);
    burger.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
  });

  /* Cierra al hacer clic fuera */
  document.addEventListener('click', function (e) {
    if (!burger.contains(e.target) && !mobileNav.contains(e.target)) {
      mobileNav.classList.remove('open');
      burger.setAttribute('aria-expanded', 'false');
    }
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
</script>
</body>
</html>
