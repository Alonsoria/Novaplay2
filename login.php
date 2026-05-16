<?php
/**
 * NOVAPLAY — LOGIN.PHP
 * Inicio de sesión con CSRF, sanitización y manejo seguro de sesión.
 */

require_once 'security.php';
require_once 'config.php';

/* Si ya está logueado, redirigir al inicio */
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* 1. Verificar token CSRF */
    csrf_verify();

    /* 2. Sanitizar inputs */
    $email    = clean_email($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    /* 3. Validar que los campos no estén vacíos */
    if (!$email) {
        $error = 'Introduce un correo electrónico válido.';
    } elseif (strlen($password) < 1) {
        $error = 'Introduce tu contraseña.';
    } else {
        /* 4. Buscar usuario — incluye campo verificado */
        $stmt = $conn->prepare(
            "SELECT id_usuario, nombre, contraseña, verificado FROM usuarios WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['contraseña'])) {
            /* Verificar que la cuenta esté activada */
            if (!(int)$user['verificado']) {
                $_SESSION['pending_verify_email'] = $email;
                header('Location: verificar.php');
                exit;
            }
            /* Sesión segura: regenerar ID antes de escribir datos */
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id_usuario'];
            $_SESSION['username'] = $user['nombre'];
            header('Location: index.php');
            exit;
        } else {
            /* Mensaje genérico para no revelar si el email existe */
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión | NovaPlay</title>
  <link rel="icon" href="./images/novaplay icono.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- ✅ Un solo CSS -->
  <link rel="stylesheet" href="style.css">
  <script>(function(){try{if(localStorage.getItem('nv-theme')==='light')document.documentElement.classList.add('light-mode');}catch(e){}})()</script>
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-card__logo">
      <img src="./images/novaplay logo 2.png" alt="NovaPlay" onerror="this.style.display='none'">
    </div>

    <h2>Iniciar Sesión</h2>

    <?php if ($error): ?>
      <div class="alert alert--error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
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

      <div class="form-group">
        <label for="password">Contraseña</label>
        <div style="position:relative;">
          <input type="password"
                 id="password"
                 name="password"
                 class="form-control"
                 placeholder="••••••••"
                 required
                 autocomplete="current-password"
                 maxlength="128"
                 style="padding-right:44px;">
          <button type="button"
                  id="togglePassword"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--clr-text-muted);cursor:pointer;"
                  aria-label="Mostrar/ocultar contraseña">
            <i class="fa-solid fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-full" style="margin-top:8px;">
        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
        Entrar
      </button>
    </form>

    <div class="auth-card__footer" style="margin-top:20px;">
      <a href="recuperar_cuenta.php" style="color:var(--clr-accent);font-size:.85rem;">
        <i class="fa-solid fa-key" aria-hidden="true"></i>
        ¿Olvidaste tu contraseña?
      </a>
    </div>

    <div class="auth-card__footer">
      ¿No tienes cuenta?
      <a href="signup.php">Regístrate gratis</a>
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
/* Mostrar/ocultar contraseña */
document.getElementById('togglePassword').addEventListener('click', function () {
  const input   = document.getElementById('password');
  const icon    = document.getElementById('eyeIcon');
  const isText  = input.type === 'text';
  input.type    = isText ? 'password' : 'text';
  icon.className = isText ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
  this.setAttribute('aria-label', isText ? 'Mostrar contraseña' : 'Ocultar contraseña');
});
</script>
</body>
</html>
