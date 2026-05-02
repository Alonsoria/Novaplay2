<?php
/**
 * NOVAPLAY — SUCCESS.PHP
 * Página de éxito después del pago. Muestra cashback y códigos de activación.
 */

$pageTitle = 'Pago Exitoso';
require_once 'header.php';
require_login();

$uid           = (int)$_SESSION['user_id'];
$cashback      = (int)($_SESSION['pago_cashback']  ?? 0);
$codigosCompra = $_SESSION['codigos_compra'] ?? [];
unset($_SESSION['pago_exitoso'], $_SESSION['pago_cashback'], $_SESSION['codigos_compra']);

/* Formato visual del código: XXXX-XXXX-XXXX-XXXX */
function formatCode(string $code): string {
    return implode('-', str_split($code, 4));
}
?>

<div class="result-page" aria-label="Pago exitoso">
  <div class="result-icon" aria-hidden="true">
    <i class="fa-solid fa-circle-check" style="color:var(--clr-success);"></i>
  </div>
  <h2 style="color:var(--clr-success);">¡Pago Exitoso!</h2>
  <p>Tu compra fue procesada correctamente.</p>

  <?php if ($cashback > 0): ?>
    <p style="font-size:.95rem;color:var(--clr-neon);font-family:var(--font-display);">
      <i class="fa-solid fa-coins" style="color:var(--clr-warning);margin-right:4px;" aria-hidden="true"></i>
      ¡Ganaste <strong><?= $cashback ?> puntos</strong> de cashback en esta compra!
    </p>
  <?php endif; ?>

  <?php if (!empty($codigosCompra)): ?>
  <!-- ── Códigos de activación ── -->
  <div class="codes-section" style="width:100%;max-width:700px;margin-top:var(--space-lg);">
    <h3 style="font-family:var(--font-display);color:var(--clr-white);margin-bottom:var(--space-md);font-size:1.2rem;text-align:left;">
      <i class="fa-solid fa-key" style="color:var(--clr-accent-2);margin-right:8px;" aria-hidden="true"></i>
      Tus códigos de activación
    </h3>
    <div class="code-list">
      <?php foreach ($codigosCompra as $idx => $item): ?>
        <div class="code-item" id="code-item-<?= $idx ?>">
          <div class="code-item__product">
            <?php if (!empty($item['imagen'])): ?>
              <img src="<?= e($item['imagen']) ?>"
                   alt="<?= e($item['nombre']) ?>"
                   class="code-item__img">
            <?php else: ?>
              <div class="code-item__img-placeholder">
                <i class="fa-solid fa-gamepad"></i>
              </div>
            <?php endif; ?>
            <span class="code-item__name"><?= e($item['nombre']) ?></span>
          </div>
          <div class="code-item__code-wrap">
            <span class="code-item__code" id="codeVal-<?= $idx ?>"><?= e(formatCode($item['codigo'])) ?></span>
            <button type="button"
                    class="copy-btn"
                    data-code="<?= e($item['codigo']) ?>"
                    data-idx="<?= $idx ?>"
                    aria-label="Copiar código de <?= e($item['nombre']) ?>">
              <i class="fa-solid fa-copy" aria-hidden="true"></i>
              <span>Copiar</span>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p style="font-size:.8rem;color:var(--clr-text-muted);margin-top:var(--space-md);">
      <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
      Guarda tus códigos en un lugar seguro. También los encontrarás en el historial de tu perfil.
    </p>
  </div>
  <?php endif; ?>

  <div style="display:flex;gap:var(--space-md);flex-wrap:wrap;justify-content:center;margin-top:var(--space-lg);">
    <a href="productos.php" class="btn">
      <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
      Seguir comprando
    </a>
    <a href="perfil.php#pedidos" class="btn2">
      <i class="fa-solid fa-box" aria-hidden="true"></i>
      Ver mis pedidos
    </a>
    <a href="index.php" class="btn2">
      <i class="fa-solid fa-house" aria-hidden="true"></i>
      Inicio
    </a>
  </div>
</div>

<script>
(function () {
  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const code = btn.dataset.code;
      const idx  = btn.dataset.idx;

      navigator.clipboard.writeText(code).then(function () {
        const icon = btn.querySelector('i');
        const text = btn.querySelector('span');
        icon.className = 'fa-solid fa-check';
        text.textContent = '¡Copiado!';
        btn.style.background = 'var(--clr-success)';
        btn.style.borderColor = 'var(--clr-success)';

        setTimeout(function () {
          icon.className = 'fa-solid fa-copy';
          text.textContent = 'Copiar';
          btn.style.background = '';
          btn.style.borderColor = '';
        }, 2000);
      }).catch(function () {
        /* Fallback para navegadores sin clipboard API */
        const el = document.createElement('textarea');
        el.value = code;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        btn.querySelector('span').textContent = '¡Copiado!';
        setTimeout(function () { btn.querySelector('span').textContent = 'Copiar'; }, 2000);
      });
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
