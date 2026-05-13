<?php
/**
 * NOVAPLAY — VERIFICAR.PHP
 * Vista para confirmar la cuenta con el código enviado por correo.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

/* Usuario ya logueado no necesita verificar */
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

/* Requiere que haya una verificación pendiente en sesión */
if (empty($_SESSION['pending_verify_email'])) {
    header('Location: login.php');
    exit;
}

$pendingEmail = $_SESSION['pending_verify_email'];
$error        = '';
$info         = '';

/* ── Obtener datos del usuario pendiente ── */
$stmtU = $conn->prepare(
    "SELECT id_usuario, nombre, verification_code, verification_expires
     FROM usuarios WHERE email = ? AND verificado = 0 LIMIT 1"
);
$stmtU->bind_param("s", $pendingEmail);
$stmtU->execute();
$usuario = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

/* Si no existe o ya fue verificado, limpiar sesión y redirigir */
if (!$usuario) {
    unset($_SESSION['pending_verify_email']);
    header('Location: login.php');
    exit;
}

/* ── Reenviar código ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reenviar') {
    csrf_verify();

    $newCode    = nova_gen_verification_code();
    $newExpires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $stmtRe = $conn->prepare(
        "UPDATE usuarios SET verification_code = ?, verification_expires = ? WHERE id_usuario = ?"
    );
    $stmtRe->bind_param("ssi", $newCode, $newExpires, $usuario['id_usuario']);
    $stmtRe->execute();
    $stmtRe->close();

    $mailBody  = "Hola {$usuario['nombre']},\n\n";
    $mailBody .= "Solicitaste un nuevo código de verificación para Novaplay.\n\n";
    $mailBody .= "Tu nuevo código es:\n\n";
    $mailBody .= "    $newCode\n\n";
    $mailBody .= "Este código expira en 15 minutos.\n\n";
    $mailBody .= "-- Novaplay.com.mx";

    $sent = nova_send_mail($pendingEmail, "[{$usuario['nombre']} | $pendingEmail] Nuevo código - Novaplay", $mailBody);
    $info = $sent
        ? 'Te enviamos un nuevo código a ' . e($pendingEmail) . '. Revisa tu bandeja de entrada.'
        : 'No pudimos enviar el correo. Intenta de nuevo en unos minutos.';

    /* Refrescar datos del usuario */
    $stmtU2 = $conn->prepare(
        "SELECT id_usuario, nombre, verification_code, verification_expires
         FROM usuarios WHERE id_usuario = ? LIMIT 1"
    );
    $stmtU2->bind_param("i", $usuario['id_usuario']);
    $stmtU2->execute();
    $usuario = $stmtU2->get_result()->fetch_assoc();
    $stmtU2->close();
}

/* ── Validar código ingresado ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'verificar') {
    csrf_verify();

    $codeInput = strtoupper(trim($_POST['codigo'] ?? ''));

    if (strlen($codeInput) === 0) {
        $error = 'Ingresa el código de verificación.';
    } elseif (new DateTime() > new DateTime($usuario['verification_expires'])) {
        $error = 'El código ha expirado. Solicita uno nuevo.';
    } elseif (!hash_equals($usuario['verification_code'], $codeInput)) {
        $error = 'Código incorrecto. Verifica el correo y vuelve a intentarlo.';
    } else {
        /* ── Código válido: activar cuenta ── */
        $stmtOK = $conn->prepare(
            "UPDATE usuarios
             SET verificado = 1, verification_code = NULL, verification_expires = NULL
             WHERE id_usuario = ?"
        );
        $stmtOK->bind_param("i", $usuario['id_usuario']);
        $stmtOK->execute();
        $stmtOK->close();

        /* Iniciar sesión automáticamente */
        unset($_SESSION['pending_verify_email']);
        session_regenerate_id(true);
        $_SESSION['user_id']  = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['nombre'];

        header('Location: index.php');
        exit;
    }
}

$codeExpired = !empty($usuario['verification_expires'])
    && new DateTime() > new DateTime($usuario['verification_expires']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificar Cuenta | NovaPlay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <script>(function(){try{if(localStorage.getItem('nv-theme')==='light')document.documentElement.classList.add('light-mode');}catch(e){}})()</script>
</head>
<body>

<div class="auth-page">
  <div class="auth-card" style="max-width:460px;">

    <div class="auth-card__logo">
      <img src="./images/logo.png" alt="NovaPlay" onerror="this.style.display='none'">
    </div>

    <h2>
      <i class="fa-solid fa-envelope-circle-check" style="color:var(--clr-accent-2);margin-right:8px;" aria-hidden="true"></i>
      Confirma tu cuenta
    </h2>

    <p style="color:var(--clr-text-muted);font-size:.9rem;text-align:center;margin-bottom:var(--space-md);">
      Enviamos un código de 6 caracteres a<br>
      <strong style="color:var(--clr-white);"><?= e($pendingEmail) ?></strong><br>
      Cópialo y pégalo aquí para activar tu cuenta.
    </p>

    <?php if ($error): ?>
      <div class="alert alert--error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($info): ?>
      <div class="alert alert--success" role="alert" style="background:rgba(34,197,94,.12);border-color:#22c55e;color:#22c55e;">
        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        <?= $info ?>
      </div>
    <?php endif; ?>

    <?php if ($codeExpired && !$info): ?>
      <div class="alert alert--error" role="alert">
        <i class="fa-solid fa-clock" aria-hidden="true"></i>
        Tu código expiró. Solicita uno nuevo.
      </div>
    <?php endif; ?>

    <!-- Formulario ingreso de código -->
    <form method="POST" action="verificar.php" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="verificar">

      <div class="form-group">
        <label for="codigo">Código de verificación</label>
        <input type="text"
               id="codigo"
               name="codigo"
               class="form-control"
               placeholder="Ej: AB3K7F"
               maxlength="6"
               required
               autocomplete="one-time-code"
               style="text-transform:uppercase;letter-spacing:.2em;font-size:1.3rem;text-align:center;"
               <?= $codeExpired ? 'disabled' : '' ?>>
      </div>

      <button type="submit" class="btn btn-full" style="margin-top:8px;" <?= $codeExpired ? 'disabled' : '' ?>>
        <i class="fa-solid fa-shield-check" aria-hidden="true"></i>
        Confirmar cuenta
      </button>
    </form>

    <!-- Reenviar código -->
    <form method="POST" action="verificar.php" style="margin-top:var(--space-md);">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="reenviar">
      <button type="submit"
              class="btn2 btn-full"
              style="font-size:.85rem;">
        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
        Reenviar código
      </button>
    </form>

    <div class="auth-card__footer" style="margin-top:var(--space-md);">
      <a href="signup.php" style="color:var(--clr-text-muted);font-size:.85rem;">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver al registro
      </a>
    </div>

  </div>
</div>

<script>
/* Forzar mayúsculas en el input de código */
document.getElementById('codigo').addEventListener('input', function () {
  this.value = this.value.toUpperCase();
});
</script>
</body>
</html>
