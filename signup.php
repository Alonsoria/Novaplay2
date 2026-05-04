<?php
/**
 * NOVAPLAY — SIGNUP.PHP
 * Registro de usuarios con CSRF, honeypot, rate limiting por IP,
 * validación completa y verificación de cuenta por correo.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    /* ── Honeypot anti-bot: campo oculto que humanos dejan vacío ── */
    if (!empty($_POST['hp_website'])) {
        /* Bot detectado: redirigir silenciosamente como si fuera éxito */
        header('Location: verificar.php');
        exit;
    }

    /* ── Rate limiting: máx 5 registros por IP en 60 minutos ── */
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    /* Limpiar intentos viejos (más de 1 hora) */
    $conn->query("DELETE FROM registro_intentos WHERE creado < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmtIP = $conn->prepare("SELECT COUNT(*) FROM registro_intentos WHERE ip = ?");
    $stmtIP->bind_param("s", $ip);
    $stmtIP->execute();
    $stmtIP->bind_result($intentos);
    $stmtIP->fetch();
    $stmtIP->close();

    if ($intentos >= 5) {
        $error = 'Demasiados intentos de registro. Espera 1 hora e inténtalo de nuevo.';
    } else {
        $username  = clean_str($_POST['username'] ?? '');
        $email     = clean_email($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        /* ── Validaciones de campos ── */
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
            /* ── Verificar duplicado de correo ── */
            $stmtChk = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmtChk->bind_param("s", $email);
            $stmtChk->execute();
            $stmtChk->store_result();
            $exists = $stmtChk->num_rows > 0;
            $stmtChk->close();

            if ($exists) {
                $error = 'Este correo ya está registrado.';
            } else {
                /* ── Crear usuario con estado no verificado ── */
                $hashed  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $code    = nova_gen_verification_code();
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $stmtIns = $conn->prepare(
                    "INSERT INTO usuarios
                        (nombre, email, contraseña, verificado, verification_code, verification_expires)
                     VALUES (?, ?, ?, 0, ?, ?)"
                );
                $stmtIns->bind_param("sssss", $username, $email, $hashed, $code, $expires);

                if ($stmtIns->execute()) {
                    /* ── Registrar intento de IP ── */
                    $stmtLog = $conn->prepare("INSERT INTO registro_intentos (ip) VALUES (?)");
                    $stmtLog->bind_param("s", $ip);
                    $stmtLog->execute();
                    $stmtLog->close();

                    /* ── Enviar correo de verificación ── */
                    $mailBody  = "Hola $username,\n\n";
                    $mailBody .= "Gracias por registrarte en Novaplay.\n\n";
                    $mailBody .= "Para activar tu cuenta, copia y pega el siguiente código en la plataforma:\n\n";
                    $mailBody .= "    $code\n\n";
                    $mailBody .= "Este código expira en 15 minutos.\n";
                    $mailBody .= "Si no creaste esta cuenta, ignora este mensaje.\n\n";
                    $mailBody .= "-- Novaplay.com.mx";

                    nova_send_mail($email, "[$username | $email] Confirma tu cuenta - Novaplay", $mailBody);

                    /* ── Guardar email en sesión para la vista de verificación ── */
                    $_SESSION['pending_verify_email'] = $email;

                    header('Location: verificar.php');
                    exit;
                } else {
                    $error = 'Error al crear la cuenta. Intenta de nuevo.';
                }
                $stmtIns->close();
            }
        }
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

      <!-- Honeypot anti-bot: oculto para humanos, los bots lo llenan -->
      <div style="display:none;" aria-hidden="true">
        <input type="text" name="hp_website" value="" tabindex="-1" autocomplete="off">
      </div>

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
