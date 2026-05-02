<?php
/**
 * NOVAPLAY — PERFIL.PHP
 * Vista de perfil del usuario: datos personales, facturación, historial.
 */

$pageTitle = 'Mi Perfil';
require_once 'header.php';
require_login();

$uid     = (int)$_SESSION['user_id'];
$success = '';
$error   = '';

/* ── Guardar cambios de perfil ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $accion = clean_str($_POST['accion'] ?? '');

    if ($accion === 'personal') {
        $nombre   = clean_str($_POST['nombre']   ?? '');
        $apellido = clean_str($_POST['apellido'] ?? '');
        $telefono = clean_str($_POST['telefono'] ?? '');
        $email    = clean_email($_POST['email']  ?? '');

        if (!$nombre || !$email) {
            $error = 'Nombre y correo son obligatorios.';
        } else {
            /* Verificar email único (excluyendo el usuario actual) */
            $stmtE = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
            $stmtE->bind_param("si", $email, $uid);
            $stmtE->execute();
            $stmtE->store_result();
            if ($stmtE->num_rows > 0) {
                $error = 'Ese correo ya está en uso por otra cuenta.';
            } else {
                $stmtU = $conn->prepare(
                    "UPDATE usuarios SET nombre=?, apellido=?, email=?, telefono=? WHERE id_usuario=?"
                );
                $stmtU->bind_param("ssssi", $nombre, $apellido, $email, $telefono, $uid);
                $stmtU->execute();
                $stmtU->close();
                $_SESSION['username'] = $nombre;
                $success = 'Datos personales actualizados correctamente.';
            }
            $stmtE->close();
        }
    } elseif ($accion === 'facturacion') {
        $direccion   = clean_str($_POST['direccion']   ?? '');
        $razon_social= clean_str($_POST['razon_social'] ?? '');

        $stmtF = $conn->prepare(
            "UPDATE usuarios SET direccion=?, razon_social=? WHERE id_usuario=?"
        );
        $stmtF->bind_param("ssi", $direccion, $razon_social, $uid);
        $stmtF->execute();
        $stmtF->close();
        $success = 'Datos de facturación actualizados.';
    } elseif ($accion === 'password') {
        $pwdActual = $_POST['pwd_actual']  ?? '';
        $pwdNueva  = $_POST['pwd_nueva']   ?? '';
        $pwdConf   = $_POST['pwd_confirm'] ?? '';

        if (strlen($pwdNueva) < 8) {
            $error = 'La contraseña nueva debe tener al menos 8 caracteres.';
        } elseif ($pwdNueva !== $pwdConf) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } else {
            $stmtPwd = $conn->prepare("SELECT contraseña FROM usuarios WHERE id_usuario = ?");
            $stmtPwd->bind_param("i", $uid);
            $stmtPwd->execute();
            $hashActual = $stmtPwd->get_result()->fetch_assoc()['contraseña'] ?? '';
            $stmtPwd->close();

            if (!password_verify($pwdActual, $hashActual)) {
                $error = 'La contraseña actual es incorrecta.';
            } else {
                $nuevoHash = password_hash($pwdNueva, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmtNP    = $conn->prepare("UPDATE usuarios SET contraseña=? WHERE id_usuario=?");
                $stmtNP->bind_param("si", $nuevoHash, $uid);
                $stmtNP->execute();
                $stmtNP->close();
                $success = 'Contraseña actualizada correctamente.';
            }
        }
    }
}

/* ── Cargar datos del usuario ── */
$stmtD = $conn->prepare(
    "SELECT nombre, apellido, email, telefono, direccion, puntos,
            razon_social, fecha_registro
     FROM usuarios WHERE id_usuario = ?"
);
$stmtD->bind_param("i", $uid);
$stmtD->execute();
$user = $stmtD->get_result()->fetch_assoc();
$stmtD->close();

/* ── Pedidos del usuario ── */
$pedidos = [];
$stmtPed = $conn->prepare(
    "SELECT id_pedido, total, estado, fecha FROM pedidos
     WHERE id_usuario = ? ORDER BY fecha DESC LIMIT 20"
);
$stmtPed->bind_param("i", $uid);
$stmtPed->execute();
$resPed = $stmtPed->get_result();
while ($row = $resPed->fetch_assoc()) $pedidos[] = $row;
$stmtPed->close();

/* Total de pedidos del usuario (para numeración #1, #2, #3...) */
$totalPedidos = 0;
$stmtCnt = $conn->prepare("SELECT COUNT(*) AS c FROM pedidos WHERE id_usuario = ?");
$stmtCnt->bind_param("i", $uid);
$stmtCnt->execute();
$totalPedidos = (int)($stmtCnt->get_result()->fetch_assoc()['c'] ?? 0);
$stmtCnt->close();

/* ── Tarjetas guardadas ── */
$tarjetas = [];
try {
    $stmtTar = $conn->prepare(
        "SELECT id, ultimos4, marca, expiry, alias FROM tarjetas_guardadas
         WHERE id_usuario = ? ORDER BY creado DESC"
    );
    $stmtTar->bind_param("i", $uid);
    $stmtTar->execute();
    $resTar = $stmtTar->get_result();
    while ($row = $resTar->fetch_assoc()) $tarjetas[] = $row;
    $stmtTar->close();
} catch (Exception $e) {}
?>

<main class="profile-page" aria-label="Mi perfil">

  <h2 style="margin-bottom:var(--space-xl);">
    <i class="fa-solid fa-user-circle" aria-hidden="true" style="color:var(--clr-accent);margin-right:8px;"></i>
    Mi Perfil
  </h2>

  <?php if ($success): ?>
    <div class="alert alert--success" role="alert" style="margin-bottom:var(--space-lg);">
      <i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e($success) ?>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert--error" role="alert" style="margin-bottom:var(--space-lg);">
      <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?>
    </div>
  <?php endif; ?>

  <!-- Puntos y nivel -->
  <div class="profile-points-banner">
    <div style="display:flex;align-items:center;gap:var(--space-md);">
      <i class="fa-solid fa-star" style="color:var(--clr-warning);font-size:2rem;" aria-hidden="true"></i>
      <div>
        <div style="font-size:.8rem;color:var(--clr-text-muted);text-transform:uppercase;letter-spacing:.06em;">Tus puntos acumulados</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--clr-neon);font-family:var(--font-display);">
          <?= number_format((int)($user['puntos'] ?? 0)) ?> pts
        </div>
      </div>
    </div>
    <div style="font-size:.82rem;color:var(--clr-text-muted);">
      Miembro desde <?= date('d/m/Y', strtotime($user['fecha_registro'] ?? 'now')) ?>
    </div>
  </div>

  <!-- Tabs de navegación -->
  <div class="profile-tabs" role="tablist">
    <button class="profile-tab active" data-tab="personal"    role="tab" aria-selected="true">
      <i class="fa-solid fa-user" aria-hidden="true"></i> Personal
    </button>
    <button class="profile-tab" data-tab="facturacion" role="tab" aria-selected="false">
      <i class="fa-solid fa-file-invoice" aria-hidden="true"></i> Facturación
    </button>
    <button class="profile-tab" data-tab="seguridad"   role="tab" aria-selected="false">
      <i class="fa-solid fa-lock" aria-hidden="true"></i> Contraseña
    </button>
    <button class="profile-tab" data-tab="pedidos" id="pedidos" role="tab" aria-selected="false">
      <i class="fa-solid fa-box" aria-hidden="true"></i> Pedidos
    </button>
    <?php if (!empty($tarjetas)): ?>
    <button class="profile-tab" data-tab="tarjetas" role="tab" aria-selected="false">
      <i class="fa-solid fa-credit-card" aria-hidden="true"></i> Tarjetas
    </button>
    <?php endif; ?>
  </div>

  <!-- ── Tab: Datos personales ── -->
  <div class="profile-tab-content active" id="tab-personal">
    <div class="profile-card">
      <h3 style="margin-bottom:var(--space-lg);">Datos personales</h3>
      <form method="POST" action="perfil.php">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="personal">
        <div class="profile-form-grid">
          <div class="form-group">
            <label for="p_nombre">Nombre *</label>
            <input type="text" id="p_nombre" name="nombre" class="form-control"
                   value="<?= e($user['nombre'] ?? '') ?>" required maxlength="100">
          </div>
          <div class="form-group">
            <label for="p_apellido">Apellido</label>
            <input type="text" id="p_apellido" name="apellido" class="form-control"
                   value="<?= e($user['apellido'] ?? '') ?>" maxlength="100">
          </div>
          <div class="form-group">
            <label for="p_email">Correo electrónico *</label>
            <input type="email" id="p_email" name="email" class="form-control"
                   value="<?= e($user['email'] ?? '') ?>" required maxlength="150">
          </div>
          <div class="form-group">
            <label for="p_telefono">Teléfono</label>
            <input type="tel" id="p_telefono" name="telefono" class="form-control"
                   value="<?= e($user['telefono'] ?? '') ?>" maxlength="20">
          </div>
        </div>
        <button type="submit" class="btn">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Guardar cambios
        </button>
      </form>
    </div>
  </div>

  <!-- ── Tab: Facturación ── -->
  <div class="profile-tab-content" id="tab-facturacion">
    <div class="profile-card">
      <h3 style="margin-bottom:var(--space-lg);">Datos de facturación</h3>
      <form method="POST" action="perfil.php">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="facturacion">
        <div class="form-group">
          <label for="f_razon">Razón social / Nombre fiscal</label>
          <input type="text" id="f_razon" name="razon_social" class="form-control"
                 value="<?= e($user['razon_social'] ?? '') ?>" maxlength="150"
                 placeholder="Persona física o empresa">
        </div>
        <div class="form-group">
          <label for="f_dir">Dirección de facturación</label>
          <textarea id="f_dir" name="direccion" class="form-control"
                    rows="3" maxlength="500"
                    placeholder="Calle, número, colonia, ciudad, CP"><?= e($user['direccion'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Guardar
        </button>
      </form>
    </div>
  </div>

  <!-- ── Tab: Contraseña ── -->
  <div class="profile-tab-content" id="tab-seguridad">
    <div class="profile-card">
      <h3 style="margin-bottom:var(--space-lg);">Cambiar contraseña</h3>
      <form method="POST" action="perfil.php" style="max-width:400px;">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="password">
        <div class="form-group">
          <label for="s_actual">Contraseña actual</label>
          <input type="password" id="s_actual" name="pwd_actual" class="form-control"
                 required autocomplete="current-password">
        </div>
        <div class="form-group">
          <label for="s_nueva">Nueva contraseña</label>
          <input type="password" id="s_nueva" name="pwd_nueva" class="form-control"
                 required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="s_confirm">Confirmar nueva contraseña</label>
          <input type="password" id="s_confirm" name="pwd_confirm" class="form-control"
                 required minlength="8" autocomplete="new-password">
        </div>
        <button type="submit" class="btn">
          <i class="fa-solid fa-key" aria-hidden="true"></i> Cambiar contraseña
        </button>
      </form>
    </div>
  </div>

  <!-- ── Tab: Historial de pedidos ── -->
  <div class="profile-tab-content" id="tab-pedidos">
    <div class="profile-card">
      <h3 style="margin-bottom:var(--space-lg);">Historial de pedidos</h3>
      <?php if (empty($pedidos)): ?>
        <div class="cart-empty" style="padding:var(--space-xl) 0;">
          <i class="fa-solid fa-box-open" aria-hidden="true"></i>
          <p>No tienes pedidos todavía.</p>
          <a href="productos.php" class="btn mt-md">Ver juegos</a>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="cart-table">
            <thead>
              <tr>
                <th scope="col"># Pedido</th>
                <th scope="col">Total</th>
                <th scope="col">Estado</th>
                <th scope="col">Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php
              /* Los pedidos vienen DESC (más nuevo primero), el más reciente
                 recibe el número más alto (= totalPedidos), el más antiguo = 1 */
              $numActual = min($totalPedidos, count($pedidos) + 0);
              foreach ($pedidos as $ped):
              ?>
                <tr>
                  <td>#<?= $numActual-- ?></td>
                  <td>$<?= number_format((float)$ped['total'], 2) ?></td>
                  <td>
                    <span style="color:var(--clr-<?= $ped['estado'] === 'pagado' ? 'success' : ($ped['estado'] === 'cancelado' ? 'danger' : 'warning') ?>);">
                      <?= ucfirst(e($ped['estado'])) ?>
                    </span>
                  </td>
                  <td style="color:var(--clr-text-muted);font-size:.85rem;">
                    <?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Tab: Tarjetas guardadas ── -->
  <?php if (!empty($tarjetas)): ?>
  <div class="profile-tab-content" id="tab-tarjetas">
    <div class="profile-card">
      <h3 style="margin-bottom:var(--space-lg);">Tarjetas guardadas</h3>
      <ul style="list-style:none;display:flex;flex-direction:column;gap:var(--space-md);">
        <?php foreach ($tarjetas as $tar): ?>
          <li style="display:flex;align-items:center;gap:var(--space-md);background:var(--clr-surface-2);border:1px solid var(--clr-border);border-radius:var(--radius-md);padding:var(--space-md);">
            <i class="fa-brands fa-<?= strtolower($tar['marca']) === 'visa' ? 'cc-visa' : (strtolower($tar['marca']) === 'mastercard' ? 'cc-mastercard' : 'credit-card') ?>"
               style="font-size:2rem;color:var(--clr-accent-2);" aria-hidden="true"></i>
            <div style="flex:1;">
              <div style="font-weight:600;color:var(--clr-white);">
                <?= e($tar['marca']) ?> •••• <?= e($tar['ultimos4']) ?>
              </div>
              <div style="font-size:.82rem;color:var(--clr-text-muted);">Vence: <?= e($tar['expiry']) ?></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

</main>

<script>
/* ── Tabs del perfil ── */
(function () {
  const tabs    = document.querySelectorAll('.profile-tab');
  const panels  = document.querySelectorAll('.profile-tab-content');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
      panels.forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      const target = document.getElementById('tab-' + tab.dataset.tab);
      if (target) target.classList.add('active');
    });
  });

  /* Abrir tab pedidos si viene del anchor #pedidos */
  if (window.location.hash === '#pedidos') {
    document.querySelector('[data-tab="pedidos"]')?.click();
  }
})();
</script>

<?php require_once 'footer.php'; ?>
