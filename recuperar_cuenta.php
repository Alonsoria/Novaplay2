<?php
/**
 * NOVAPLAY — RECUPERAR_CUENTA.PHP
 * Solicita un enlace de un solo uso para restablecer la contraseña.
 * No revela si el correo existe (anti-enumeración).
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = clean_email($_POST['email'] ?? '');

    if (!$email) {
        $error = 'Introduce un correo electrónico válido.';
    } else {
        /* Solo cuentas ya verificadas pueden recuperar contraseña */
        $stmt = $conn->prepare(
            "SELECT id_usuario, nombre FROM usuarios WHERE email = ? AND verificado = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $uid     = (int)$user['id_usuario'];

            $stmtU = $conn->prepare(
                "UPDATE usuarios SET reset_token = ?, reset_token_expires = ? WHERE id_usuario = ?"
            );

            if (!$stmtU) {
                /* Las columnas reset_token no existen — ejecutar schema_recuperacion.sql */
                error_log('[Novaplay] recuperar_cuenta: prepare() falló — ' . $conn->error);
            } else {
                $stmtU->bind_param("ssi", $token, $expires, $uid);
                $stmtU->execute();
                $stmtU->close();

                /* Solo enviar correo si el token quedó guardado correctamente */
                $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host      = $_SERVER['HTTP_HOST'];
                $basePath  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $resetLink = $protocol . '://' . $host . $basePath
                           . '/restablecer_contrasena.php?token=' . urlencode($token);

                $nombre    = $user['nombre'];
                $mailBody  = "Hola $nombre,\n\n";
                $mailBody .= "Recibimos una solicitud para restablecer la contraseña de tu cuenta en Novaplay.\n\n";
                $mailBody .= "Haz clic (o copia y pega) el siguiente enlace para crear una nueva contraseña:\n\n";
                $mailBody .= "    $resetLink\n\n";
                $mailBody .= "Este enlace expira en 1 hora y solo puede usarse una vez.\n";
                $mailBody .= "Si no solicitaste este cambio, ignora este correo — tu contraseña no será modificada.\n\n";
                $mailBody .= "— Equipo Novaplay";

                if (!nova_send_mail($email, "Restablecer contraseña — Novaplay", $mailBody)) {
                    error_log('[Novaplay] recuperar_cuenta: fallo al enviar correo a ' . $email);
                }
            }
        }

        /* Respuesta idéntica si el correo existe o no (anti-enumeración) */
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Cuenta | NovaPlay</title>
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

    <?php if ($success): ?>

      <h2>Correo Enviado</h2>

      <div class="alert" role="status"
           style="background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.35);
                  color:var(--clr-success);border-radius:var(--radius-sm);padding:12px 14px;
                  display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
        <i class="fa-solid fa-circle-check" style="margin-top:2px;flex-shrink:0;" aria-hidden="true"></i>
        <span>
          Si ese correo está registrado y verificado, recibirás un enlace para restablecer
          tu contraseña en los próximos minutos.
        </span>
      </div>

      <p style="font-size:.84rem;color:var(--clr-text-muted);text-align:center;margin-bottom:4px;">
        Revisa tu bandeja de entrada y la carpeta de <strong style="color:var(--clr-text);">spam</strong>.
        El enlace expira en <strong style="color:var(--clr-text);">1 hora</strong>.
      </p>

    <?php else: ?>

      <h2>Recuperar Cuenta</h2>

      <p style="font-size:.88rem;color:var(--clr-text-muted);margin-bottom:16px;text-align:center;">
        Introduce tu correo y te enviaremos un enlace para crear una nueva contraseña.
      </p>

      <?php if ($error): ?>
        <div class="alert alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="recuperar_cuenta.php" novalidate>
        <?= csrf_field() ?>

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

        <button type="submit" class="btn btn-full" style="margin-top:8px;">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
          Enviar enlace de recuperación
        </button>
      </form>

    <?php endif; ?>

    <div class="auth-card__footer" style="margin-top:16px;">
      <a href="login.php">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver al inicio de sesión
      </a>
    </div>

  </div>
</div>

</body>
</html>
