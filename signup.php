<?php
/**
 * NOVAPLAY — SIGNUP.PHP
 * Registro de usuarios con CSRF, validación y password_hash.
 */

require_once 'security.php';
require_once 'config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username  = clean_str($_POST['username'] ?? '');
    $email     = clean_email($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    /* Validaciones */
    if (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'El nombre de usuario debe tener entre 3 y 30 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'El nombre de usuario solo puede contener letras, números y guiones bajos.';
    } elseif (!$email) {
        $error = 'Introduce un correo electrónico válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        /* Verificar si el email ya existe */
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Este correo ya está registrado.';
        } else {
            $stmt->close();

            /* Hash seguro de la contraseña */
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            /* Insertar usuario con nombres de columnas correctos */
            $stmt2 = $conn->prepare("INSERT INTO usuarios (nombre, email, contraseña) VALUES (?, ?, ?)");
            $stmt2->bind_param("sss", $username, $email, $hashed);

            if ($stmt2->execute()) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $stmt2->insert_id;
                $_SESSION['username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Error al crear la cuenta. Intenta de nuevo.';
            }
            $stmt2->close();
        }

        if (isset($stmt) && $stmt->errno === 0) $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Cuenta | NovaPlay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-page">
  <div class="auth-card" style="max-width:480px;">

    <div class="auth-card__logo">
      <img src="./images/logo.png" alt="NovaPlay" onerror="this.style.display='none'">
    </div>

    <h2>Crear Cuenta</h2>

    <?php if ($error): ?>
      <div class="alert alert--error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="signup.php" novalidate id="signupForm">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="username">Nombre de usuario</label>
        <input type="text"
               id="username"
               name="username"
               class="form-control"
               placeholder="GamerXyz123"
               value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>"
               required
               autocomplete="username"
               maxlength="30"
               pattern="[a-zA-Z0-9_]+">
        <small class="form-error d-none" id="usernameError">Solo letras, números y guiones bajos.</small>
      </div>

      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email"
               id="email"
               name="email"
               class="form-control"
               placeholder="tu@correo.com"
               value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
               required
               autocomplete="email"
               maxlength="254">
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div style="position:relative;">
          <input type="password"
                 id="password"
                 name="password"
                 class="form-control"
                 placeholder="Mínimo 8 caracteres"
                 required
                 autocomplete="new-password"
                 maxlength="128"
                 style="padding-right:44px;">
          <button type="button" id="togglePwd"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--clr-text-muted);cursor:pointer;"
                  aria-label="Mostrar contraseña">
            <i class="fa-solid fa-eye" id="eyeIcon1"></i>
          </button>
        </div>
        <!-- Medidor de fortaleza -->
        <div id="pwdStrength" style="height:4px;border-radius:2px;margin-top:6px;background:var(--clr-border);transition:background .3s;"></div>
        <small id="pwdStrengthLabel" class="text-muted" style="font-size:.75rem;"></small>
      </div>

      <div class="form-group">
        <label for="password2">Confirmar contraseña</label>
        <input type="password"
               id="password2"
               name="password2"
               class="form-control"
               placeholder="Repite la contraseña"
               required
               autocomplete="new-password"
               maxlength="128">
        <small class="form-error d-none" id="pwdMatchError">Las contraseñas no coinciden.</small>
      </div>

      <button type="submit" class="btn btn-full" style="margin-top:8px;">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
        Crear Cuenta
      </button>
    </form>

    <div class="auth-card__footer">
      ¿Ya tienes cuenta?
      <a href="login.php">Inicia sesión</a>
    </div>

    <div class="auth-card__footer" style="margin-top:8px;">
      <a href="index.php" style="color:var(--clr-text-muted);font-size:.85rem;">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver al inicio
      </a>
    </div>

  </div>
</div>

<script>
/* ─── Mostrar/ocultar contraseña ─── */
document.getElementById('togglePwd').addEventListener('click', function () {
  const input = document.getElementById('password');
  const icon  = document.getElementById('eyeIcon1');
  const show  = input.type === 'password';
  input.type  = show ? 'text' : 'password';
  icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

/* ─── Medidor de fortaleza ─── */
document.getElementById('password').addEventListener('input', function () {
  const val   = this.value;
  const bar   = document.getElementById('pwdStrength');
  const label = document.getElementById('pwdStrengthLabel');
  let score   = 0;
  if (val.length >= 8)              score++;
  if (/[A-Z]/.test(val))            score++;
  if (/[0-9]/.test(val))            score++;
  if (/[^A-Za-z0-9]/.test(val))     score++;

  const colors = ['#ef4444','#f59e0b','#22c55e','#22c55e'];
  const labels = ['Muy débil','Débil','Segura','Muy segura'];
  bar.style.width      = (score * 25) + '%';
  bar.style.background = colors[score - 1] || 'var(--clr-border)';
  label.textContent    = val.length ? labels[score - 1] || '' : '';
});

/* ─── Validación nombre de usuario ─── */
document.getElementById('username').addEventListener('input', function () {
  const errEl = document.getElementById('usernameError');
  const valid = /^[a-zA-Z0-9_]+$/.test(this.value);
  errEl.classList.toggle('d-none', valid || this.value === '');
  this.classList.toggle('is-invalid', !valid && this.value !== '');
});

/* ─── Validación coincidencia de contraseñas ─── */
document.getElementById('password2').addEventListener('input', function () {
  const errEl = document.getElementById('pwdMatchError');
  const match = this.value === document.getElementById('password').value;
  errEl.classList.toggle('d-none', match);
  this.classList.toggle('is-invalid', !match);
});
</script>
</body>
</html>
