<?php
/**
 * NOVAPLAY — RESTABLECER_CONTRASENA.PHP
 * Permite al usuario cambiar su contraseña usando el token de un solo uso
 * enviado a su correo desde recuperar_cuenta.php.
 */

require_once 'security.php';
require_once 'config.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;
$user  = null;

/* ── Validar token: debe existir, no estar expirado y pertenecer a cuenta verificada ── */
if ($token !== '') {
    $stmt = $conn->prepare(
        "SELECT id_usuario, nombre
         FROM usuarios
         WHERE reset_token = ? AND reset_token_expires > NOW() AND verificado = 1
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$tokenInvalido = !$token || !$user;

/* ── Procesar cambio de contraseña ── */
if (!$tokenInvalido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $newPass  = $_POST['password']  ?? '';
    $newPass2 = $_POST['password2'] ?? '';

    if (strlen($newPass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!preg_match('/[A-Z]/', $newPass)) {
        $error = 'La contraseña debe contener al menos una letra mayúscula.';
    } elseif (!preg_match('/[0-9]/', $newPass)) {
        $error = 'La contraseña debe contener al menos un número.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $newPass)) {
        $error = 'La contraseña debe contener al menos un carácter especial (ej. !, @, #, $).';
    } elseif ($newPass !== $newPass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);

        /* Actualizar contraseña y eliminar token (uso único) */
        $stmtU = $conn->prepare(
            "UPDATE usuarios
             SET contraseña = ?, reset_token = NULL, reset_token_expires = NULL
             WHERE id_usuario = ?"
        );
        $stmtU->bind_param("si", $hashed, $user['id_usuario']);
        $stmtU->execute();
        $stmtU->close();

        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva Contraseña | NovaPlay</title>
  <link rel="icon" href="./images/novaplay icono.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <script>(function(){try{if(localStorage.getItem('nv-theme')==='light')document.documentElement.classList.add('light-mode');}catch(e){}})()</script>
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-card__logo">
      <img src="./images/novaplay logo 2.png" alt="NovaPlay" onerror="this.style.display='none'">
    </div>

    <?php if ($tokenInvalido): ?>

      <!-- ── Enlace inválido o expirado ── -->
      <h2 style="color:var(--clr-danger, #ef4444);">
        <i class="fa-solid fa-link-slash" style="margin-right:8px;" aria-hidden="true"></i>
        Enlace inválido
      </h2>

      <div class="alert alert--error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        Este enlace no es válido o ya expiró (vigencia de 1 hora). Solicita uno nuevo.
      </div>

      <a href="recuperar_cuenta.php" class="btn btn-full" style="margin-top:16px;">
        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
        Solicitar nuevo enlace
      </a>

    <?php elseif ($done): ?>

      <!-- ── Contraseña cambiada con éxito ── -->
      <h2>¡Contraseña restablecida!</h2>

      <div class="alert" role="status"
           style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);
                  color:var(--clr-success);border-radius:var(--radius-sm);padding:12px 14px;
                  display:flex;align-items:flex-start;gap:10px;margin-bottom:16px;">
        <i class="fa-solid fa-circle-check" style="margin-top:2px;flex-shrink:0;" aria-hidden="true"></i>
        <span>Tu contraseña fue actualizada correctamente. Ya puedes iniciar sesión con tu nueva contraseña.</span>
      </div>

      <a href="login.php" class="btn btn-full">
        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
        Iniciar sesión
      </a>

    <?php else: ?>

      <!-- ── Formulario de nueva contraseña ── -->
      <h2>Nueva Contraseña</h2>

      <p style="font-size:.88rem;color:var(--clr-text-muted);margin-bottom:16px;text-align:center;">
        Elige una contraseña segura para
        <strong style="color:var(--clr-text);"><?= e($user['nombre']) ?></strong>.
      </p>

      <?php if ($error): ?>
        <div class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <!-- El token viaja en la query string para que esté disponible durante el POST -->
      <form method="POST"
            action="restablecer_contrasena.php?token=<?= urlencode($token) ?>"
            novalidate id="resetForm">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="password">Nueva contraseña</label>
          <div style="position:relative;">
            <input type="password"
                   id="password"
                   name="password"
                   class="form-control"
                   placeholder="Mín. 8 chars, mayúscula, número y símbolo"
                   required
                   autocomplete="new-password"
                   maxlength="128"
                   style="padding-right:44px;">
            <button type="button" id="togglePwd"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                           background:none;border:none;color:var(--clr-text-muted);cursor:pointer;"
                    aria-label="Mostrar contraseña">
              <i class="fa-solid fa-eye" id="eyeIcon1"></i>
            </button>
          </div>
          <!-- Barra de fortaleza -->
          <div id="pwdStrength"
               style="height:4px;border-radius:2px;margin-top:6px;
                      background:var(--clr-border);transition:background .3s;"></div>
          <small id="pwdStrengthLabel" style="font-size:.75rem;color:var(--clr-text-muted);"></small>
          <!-- Checklist de requisitos -->
          <ul id="pwdReqs" style="list-style:none;padding:0;margin:8px 0 0;font-size:.78rem;display:none;">
            <li id="req-len"     style="margin:2px 0;color:var(--clr-text-muted);"><i class="fa-solid fa-circle-xmark" style="margin-right:5px;"></i>Mínimo 8 caracteres</li>
            <li id="req-upper"   style="margin:2px 0;color:var(--clr-text-muted);"><i class="fa-solid fa-circle-xmark" style="margin-right:5px;"></i>Al menos una mayúscula</li>
            <li id="req-num"     style="margin:2px 0;color:var(--clr-text-muted);"><i class="fa-solid fa-circle-xmark" style="margin-right:5px;"></i>Al menos un número</li>
            <li id="req-special" style="margin:2px 0;color:var(--clr-text-muted);"><i class="fa-solid fa-circle-xmark" style="margin-right:5px;"></i>Al menos un carácter especial (!, @, #, $…)</li>
          </ul>
        </div>

        <div class="form-group">
          <label for="password2">Confirmar nueva contraseña</label>
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
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          Guardar nueva contraseña
        </button>
      </form>

      <div class="auth-card__footer" style="margin-top:16px;">
        <a href="login.php">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          Volver al inicio de sesión
        </a>
      </div>

    <?php endif; ?>

  </div>
</div>

<script>
/* Mostrar/ocultar contraseña */
const togglePwd = document.getElementById('togglePwd');
if (togglePwd) {
  togglePwd.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon1');
    const show  = input.type === 'password';
    input.type     = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    this.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
}

/* Medidor de fortaleza + checklist de requisitos */
const pwdInput = document.getElementById('password');
if (pwdInput) {
  pwdInput.addEventListener('input', function () {
    const val   = this.value;
    const bar   = document.getElementById('pwdStrength');
    const label = document.getElementById('pwdStrengthLabel');
    const reqs  = document.getElementById('pwdReqs');

    const checks = {
      len:     val.length >= 8,
      upper:   /[A-Z]/.test(val),
      num:     /[0-9]/.test(val),
      special: /[^A-Za-z0-9]/.test(val),
    };

    reqs.style.display = val.length ? 'block' : 'none';

    Object.entries(checks).forEach(function ([key, ok]) {
      const li   = document.getElementById('req-' + key);
      const icon = li.querySelector('i');
      li.style.color     = ok ? '#22c55e' : 'var(--clr-text-muted)';
      icon.className     = ok ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
      icon.style.marginRight = '5px';
    });

    const score  = Object.values(checks).filter(Boolean).length;
    const colors = ['#ef4444', '#f59e0b', '#eab308', '#22c55e'];
    const labels = ['Muy débil', 'Débil', 'Casi segura', 'Muy segura'];
    bar.style.width      = (score * 25) + '%';
    bar.style.background = colors[score - 1] || 'var(--clr-border)';
    label.textContent    = val.length ? (labels[score - 1] || '') : '';
  });
}

/* Validación en tiempo real de coincidencia */
const pwd2 = document.getElementById('password2');
if (pwd2) {
  pwd2.addEventListener('input', function () {
    const errEl = document.getElementById('pwdMatchError');
    const match = this.value === document.getElementById('password').value;
    errEl.classList.toggle('d-none', match);
    this.classList.toggle('is-invalid', !match);
  });
}

/* Bloquear envío si la contraseña no cumple los requisitos mínimos */
const resetForm = document.getElementById('resetForm');
if (resetForm) {
  resetForm.addEventListener('submit', function (e) {
    const val = document.getElementById('password').value;
    if (
      val.length < 8          ||
      !/[A-Z]/.test(val)      ||
      !/[0-9]/.test(val)      ||
      !/[^A-Za-z0-9]/.test(val)
    ) {
      e.preventDefault();
      document.getElementById('pwdReqs').style.display = 'block';
      document.getElementById('password').focus();
    }
  });
}
</script>
</body>
</html>
