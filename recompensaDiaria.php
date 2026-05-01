<?php
/**
 * NOVAPLAY — RECOMPENSADIARIA.PHP
 * Sistema de recompensa diaria por login.
 */

$pageTitle = 'Recompensa Diaria';
require_once 'header.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$msg = '';
$msgType = '';

/* ── Manejar reclamación (POST) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    /* Lógica original de reclamación sin modificar */
    $hoy = date('Y-m-d');
    $stmt = $conn->prepare("SELECT ultima_recompensa FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (($res['ultima_recompensa'] ?? '') === $hoy) {
        $msg     = '¡Ya reclamaste tu recompensa de hoy! Vuelve mañana.';
        $msgType = 'error';
    } else {
        $stmt2 = $conn->prepare("UPDATE usuarios SET ultima_recompensa = ?, puntos = puntos + 50 WHERE id_usuario = ?");
        $stmt2->bind_param("si", $hoy, $uid);
        $stmt2->execute();
        $stmt2->close();
        $msg     = '¡Felicidades! Reclamaste 50 puntos de recompensa hoy. 🎉';
        $msgType = 'success';
    }
}

/* ── Cargar datos del usuario ── */
$stmtU = $conn->prepare("SELECT puntos, ultima_recompensa FROM usuarios WHERE id_usuario = ?");
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$puntos  = (int)($userData['puntos'] ?? 0);
$hoy     = date('Y-m-d');
$claimed = ($userData['ultima_recompensa'] ?? '') === $hoy;

/* Días de la semana para el grid */
$dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$recompensas = [10, 15, 20, 25, 30, 40, 50]; // Puntos por día
$diaActual   = (int)date('N') - 1; // 0=Lunes, 6=Domingo
?>

<main class="reward-page" aria-label="Recompensa diaria">

  <h2>🎁 Recompensa Diaria</h2>
  <p class="reward-subtitle">Ingresa cada día para acumular puntos y canjearlos en tu próxima compra.</p>

  <!-- Puntos actuales -->
  <div style="background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--radius-lg);padding:var(--space-lg);margin-bottom:var(--space-xl);display:inline-flex;align-items:center;gap:var(--space-md);">
    <i class="fa-solid fa-star" style="color:var(--clr-warning);font-size:2rem;" aria-hidden="true"></i>
    <div>
      <div style="font-size:.8rem;color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:.06em;">Tus puntos</div>
      <div style="font-size:2rem;font-weight:700;color:var(--clr-neon);font-family:var(--font-display);">
        <?= number_format($puntos) ?> pts
      </div>
    </div>
  </div>

  <!-- Mensaje de resultado -->
  <?php if ($msg): ?>
    <div class="alert alert--<?= $msgType === 'success' ? 'success' : 'error' ?>" role="alert" style="margin-bottom:var(--space-lg);">
      <?= e($msg) ?>
    </div>
  <?php endif; ?>

  <!-- Grid de días -->
  <div class="reward-grid" aria-label="Recompensas de la semana">
    <?php foreach ($dias as $i => $dia): ?>
      <?php
      $estado = '';
      if ($i < $diaActual)    $estado = 'claimed';
      elseif ($i === $diaActual) $estado = 'today';
      else                    $estado = 'locked';

      $iconos = ['💰','⭐','🎮','💎','🏆','🎁','👑'];
      ?>
      <div class="reward-day <?= $estado ?>" aria-label="<?= e($dia) ?>: <?= $recompensas[$i] ?> puntos">
        <span class="reward-day__day"><?= e($dia) ?></span>
        <span class="reward-day__icon" aria-hidden="true"><?= $iconos[$i] ?></span>
        <span class="reward-day__value">+<?= $recompensas[$i] ?> pts</span>
        <?php if ($estado === 'claimed'): ?>
          <i class="fa-solid fa-check" style="color:var(--clr-success);font-size:.8rem;" aria-hidden="true"></i>
        <?php elseif ($estado === 'locked'): ?>
          <i class="fa-solid fa-lock" style="color:var(--clr-border);font-size:.8rem;" aria-hidden="true"></i>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Botón de reclamar -->
  <?php if (!$claimed): ?>
    <form method="POST" action="recompensaDiaria.php">
      <?= csrf_field() ?>
      <button type="submit" class="btn reward-claim-btn">
        <i class="fa-solid fa-gift" aria-hidden="true"></i>
        ¡Reclamar recompensa de hoy! (+<?= $recompensas[$diaActual] ?> pts)
      </button>
    </form>
  <?php else: ?>
    <div style="margin-top:var(--space-lg);">
      <p class="text-muted">Ya reclamaste tu recompensa de hoy. ¡Vuelve mañana!</p>
      <a href="productos.php" class="btn mt-md">
        <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
        Ver juegos
      </a>
    </div>
  <?php endif; ?>

</main>

<?php require_once 'footer.php'; ?>
