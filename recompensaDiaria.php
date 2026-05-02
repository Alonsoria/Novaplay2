<?php
/**
 * NOVAPLAY — RECOMPENSADIARIA.PHP
 * Sistema de recompensa diaria por login con racha (streak) semanal.
 * Día 1-3: 3 pts | Día 4-6: 5 pts | Día 7: 7 pts → se reinicia
 */

$pageTitle = 'Recompensa Diaria';
require_once 'header.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$msg = '';
$msgType = '';

/* Puntos por día del ciclo de 7 días (índice 0=Día1 … 6=Día7) */
$puntosXDia = [3, 3, 3, 5, 5, 5, 7];
$hoy  = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));

/* ── Manejar reclamación (POST) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $stmtR = $conn->prepare(
        "SELECT ultima_recompensa, racha_recompensa FROM usuarios WHERE id_usuario = ?"
    );
    $stmtR->bind_param("i", $uid);
    $stmtR->execute();
    $row = $stmtR->get_result()->fetch_assoc();
    $stmtR->close();

    $ultimaRecompensa = $row['ultima_recompensa'] ?? '';
    $rachaActualPost  = (int)($row['racha_recompensa'] ?? 0);

    if ($ultimaRecompensa === $hoy) {
        $msg     = '¡Ya reclamaste tu recompensa de hoy! Vuelve mañana.';
        $msgType = 'error';
    } else {
        /* Calcular nueva racha */
        if ($ultimaRecompensa === $ayer && $rachaActualPost > 0) {
            /* Día consecutivo: incrementar racha, reiniciar si completa el ciclo */
            $nuevaRacha = ($rachaActualPost >= 7) ? 1 : $rachaActualPost + 1;
        } else {
            /* Primer día o racha rota */
            $nuevaRacha = 1;
        }

        /* Puntos del día actual (nueva racha, base 1 → índice 0) */
        $ptsHoy = $puntosXDia[$nuevaRacha - 1];

        $stmtU = $conn->prepare(
            "UPDATE usuarios
             SET ultima_recompensa = ?, racha_recompensa = ?, puntos = puntos + ?
             WHERE id_usuario = ?"
        );
        $stmtU->bind_param("siii", $hoy, $nuevaRacha, $ptsHoy, $uid);
        $stmtU->execute();
        $stmtU->close();

        $msg     = "¡Felicidades! Reclamaste <strong>+$ptsHoy puntos</strong> (Día $nuevaRacha del ciclo).";
        $msgType = 'success';
    }
}

/* ── Cargar datos del usuario ── */
$stmtU = $conn->prepare(
    "SELECT puntos, ultima_recompensa, racha_recompensa FROM usuarios WHERE id_usuario = ?"
);
$stmtU->bind_param("i", $uid);
$stmtU->execute();
$userData = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

$puntos           = (int)($userData['puntos'] ?? 0);
$claimed          = ($userData['ultima_recompensa'] ?? '') === $hoy;
$rachaActual      = (int)($userData['racha_recompensa'] ?? 0);
$consecutiveYest  = ($userData['ultima_recompensa'] ?? '') === $ayer;

/*
 * Determinar el estado de cada día del grid:
 *   claimed    → ya reclamado (hoy = rachaActual)
 *   today      → disponible para reclamar ahora
 *   locked     → aún no disponible
 */
if ($claimed) {
    /* Hoy ya fue reclamado: días 1…rachaActual = claimed, resto locked */
    $diaDestacado = $rachaActual;
} elseif ($consecutiveYest && $rachaActual > 0) {
    /* Se puede reclamar hoy (continuación de racha) */
    $diaDestacado = ($rachaActual >= 7) ? 1 : $rachaActual + 1;
} else {
    /* Primera vez o racha rota: el día disponible es el 1 */
    $diaDestacado = 1;
}

/* Puntos que se ganarían hoy si no se ha reclamado */
$ptsHoy = $puntosXDia[$diaDestacado - 1];
?>

<main class="reward-page" aria-label="Recompensa diaria">

  <h2>
    <i class="fa-solid fa-gift" aria-hidden="true" style="color:var(--clr-accent);margin-right:8px;"></i>
    Recompensa Diaria
  </h2>
  <p class="reward-subtitle">Ingresa cada día para acumular puntos. El ciclo es de 7 días y se reinicia automáticamente.</p>

  <!-- Puntos actuales -->
  <div class="reward-points-box">
    <i class="fa-solid fa-star" style="color:var(--clr-warning);font-size:2rem;" aria-hidden="true"></i>
    <div>
      <div style="font-size:.8rem;color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:.06em;">Tus puntos</div>
      <div style="font-size:2rem;font-weight:700;color:var(--clr-neon);font-family:var(--font-display);">
        <?= number_format($puntos) ?> pts
      </div>
    </div>
    <?php if ($rachaActual > 0): ?>
    <div style="margin-left:auto;text-align:right;">
      <div style="font-size:.8rem;color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:.06em;">Racha actual</div>
      <div style="font-size:1.4rem;font-weight:700;color:var(--clr-accent-2);font-family:var(--font-display);">
        🔥 <?= $rachaActual ?> día<?= $rachaActual > 1 ? 's' : '' ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Mensaje de resultado -->
  <?php if ($msg): ?>
    <div class="alert alert--<?= $msgType === 'success' ? 'success' : 'error' ?>"
         role="alert"
         style="margin-bottom:var(--space-lg);text-align:left;">
      <?= $msg ?>
    </div>
  <?php endif; ?>

  <!-- Grid de días del ciclo -->
  <div class="reward-grid" aria-label="Ciclo de recompensas (7 días)">
    <?php
    $etiquetas = ['Día 1','Día 2','Día 3','Día 4','Día 5','Día 6','Día 7'];
    $iconos    = ['coins','star','gamepad','gem','trophy','gift','crown'];
    foreach ($puntosXDia as $i => $pts):
      $dayNum = $i + 1;

      if ($claimed) {
          $estado = ($dayNum <= $rachaActual) ? 'claimed' : 'locked';
      } elseif ($consecutiveYest && $rachaActual > 0) {
          /* Continuación de racha: días < diaDestacado = claimed */
          if ($dayNum < $diaDestacado) $estado = 'claimed';
          elseif ($dayNum === $diaDestacado) $estado = 'today';
          else $estado = 'locked';
      } else {
          $estado = ($dayNum === 1) ? 'today' : 'locked';
      }
    ?>
      <div class="reward-day <?= $estado ?>"
           aria-label="<?= $etiquetas[$i] ?>: <?= $pts ?> puntos">
        <span class="reward-day__day"><?= $etiquetas[$i] ?></span>
        <span class="reward-day__icon" aria-hidden="true">
          <i class="fa-solid fa-<?= $iconos[$i] ?>"></i>
        </span>
        <span class="reward-day__value">+<?= $pts ?> pts</span>
        <?php if ($estado === 'claimed'): ?>
          <i class="fa-solid fa-check"
             style="color:var(--clr-success);font-size:.8rem;"
             aria-hidden="true"></i>
        <?php elseif ($estado === 'locked'): ?>
          <i class="fa-solid fa-lock"
             style="color:var(--clr-border);font-size:.8rem;"
             aria-hidden="true"></i>
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
        ¡Reclamar +<?= $ptsHoy ?> puntos hoy! (Día <?= $diaDestacado ?>)
      </button>
    </form>
  <?php else: ?>
    <div style="margin-top:var(--space-lg);text-align:center;">
      <p class="text-muted">
        <i class="fa-solid fa-clock" aria-hidden="true"></i>
        Ya reclamaste tu recompensa de hoy. ¡Vuelve mañana para el Día <?= ($rachaActual >= 7) ? 1 : $rachaActual + 1 ?>!
      </p>
      <a href="productos.php" class="btn mt-md">
        <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
        Ver juegos
      </a>
    </div>
  <?php endif; ?>

</main>

<?php require_once 'footer.php'; ?>
