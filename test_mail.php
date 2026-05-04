<?php
/**
 * NOVAPLAY — TEST_MAIL.PHP
 * Diagnóstico y prueba de envío de correo SMTP.
 * SOLO para desarrollo local. Eliminar o proteger en producción.
 */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer.php';

$testTo    = trim($_POST['test_to'] ?? '');
$resultado = null;
$errorMsg  = '';

$esMailtrap    = str_contains(MAIL_HOST, 'mailtrap');
$destinoValido = strlen($testTo) > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $destinoValido) {
    $ok = nova_send_mail(
        $testTo,
        '[TEST] Prueba de correo - Novaplay',
        "Este es un correo de prueba enviado desde Novaplay.\n\nSi ves esto en Mailtrap, el SMTP funciona correctamente."
    );
    $resultado = $ok ? 'OK' : 'ERROR';
    if (!$ok) {
        $errorMsg = 'El envío falló. Revisa el log de PHP en C:\\xampp\\php\\logs\\php_error_log para ver el detalle.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test de Correo | NovaPlay</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .diag-box { background:#0d0d1a; border:1px solid var(--clr-border,#2a2a4a); border-radius:8px; padding:20px; margin:12px 0; }
    .diag-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid rgba(255,255,255,.05); font-size:.88rem; }
    .diag-row:last-child { border-bottom:none; }
    .badge-ok  { color:#22c55e; font-weight:700; }
    .badge-err { color:#ef4444; font-weight:700; }
    .badge-warn{ color:#f59e0b; font-weight:700; }
    pre { background:#070714; border:1px solid #1e1e3a; border-radius:6px; padding:14px; font-size:.78rem; overflow-x:auto; white-space:pre-wrap; word-break:break-all; color:#a0a0c0; max-height:300px; overflow-y:auto; }
  </style>
</head>
<body>
<div class="auth-page" style="align-items:flex-start; padding-top:40px;">
  <div class="auth-card" style="max-width:680px; width:100%;">

    <h2 style="margin-bottom:4px;">
      <i class="fa-solid fa-envelope-open-text" style="color:var(--clr-accent-2);margin-right:8px;"></i>
      Diagnóstico de Correo SMTP
    </h2>
    <p style="color:var(--clr-text-muted);font-size:.85rem;margin-bottom:var(--space-lg);">
      Elimina este archivo antes de subir a producción.
    </p>

    <!-- ── Estado de configuración actual ── -->
    <h3 style="font-size:.95rem; color:var(--clr-text-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px;">
      Configuración actual <small style="font-size:.75rem;">(config.php)</small>
    </h3>
    <div class="diag-box">
      <div class="diag-row"><span>MAIL_HOST</span>
        <strong class="badge-ok"><?= e(MAIL_HOST) ?> <?= $esMailtrap ? '✓ Mailtrap' : '' ?></strong>
      </div>
      <div class="diag-row"><span>MAIL_PORT</span><strong><?= e((string)MAIL_PORT) ?></strong></div>
      <div class="diag-row"><span>MAIL_USER</span><strong class="badge-ok"><?= e(MAIL_USER) ?> ✓</strong></div>
      <div class="diag-row"><span>MAIL_PASS</span><strong class="badge-ok"><?= str_repeat('•', 12) ?> (configurada)</strong></div>
      <div class="diag-row"><span>MAIL_FROM</span><strong><?= e(MAIL_FROM) ?></strong></div>
      <div class="diag-row"><span>Cifrado</span><strong><?= MAIL_PORT == 465 ? 'SMTPS (SSL/465)' : 'STARTTLS (587)' ?></strong></div>
    </div>

    <?php if ($esMailtrap): ?>
    <div class="diag-box" style="border-color:#6366f155;">
      <p style="color:#818cf8;font-weight:700;margin-bottom:6px;">
        <i class="fa-solid fa-circle-info"></i> Modo Mailtrap (sandbox)
      </p>
      <p style="color:var(--clr-text-muted);font-size:.87rem;margin:0;">
        Todos los correos van a tu bandeja en
        <strong style="color:var(--clr-white);">mailtrap.io → Email Testing → tu inbox</strong>.
        El campo "destino" puede ser cualquier dirección — Mailtrap los intercepta todos.
      </p>
    </div>
    <?php endif; ?>

    <!-- ── Formulario de prueba ── -->
    <h3 style="font-size:.95rem; color:var(--clr-text-muted); text-transform:uppercase; letter-spacing:.05em; margin:var(--space-md) 0 8px;">
      Enviar correo de prueba
    </h3>

    <?php if ($resultado === 'OK'): ?>
      <div class="alert" style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#22c55e;border-radius:8px;padding:12px 16px;margin-bottom:12px;">
        <i class="fa-solid fa-circle-check"></i>
        <strong>¡Correo enviado!</strong>
        Revisa tu bandeja en <strong>mailtrap.io → Email Testing → tu inbox</strong>.
      </div>
    <?php elseif ($resultado === 'ERROR'): ?>
      <div class="alert alert--error" style="margin-bottom:8px;">
        <i class="fa-solid fa-circle-xmark"></i>
        <strong>Falló el envío.</strong> <?= e($errorMsg) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="test_mail.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="flex:1;min-width:220px;margin:0;">
        <label for="test_to" style="font-size:.85rem;">
          Correo destino
          <?php if ($esMailtrap): ?>
            <small style="color:#818cf8;">(cualquier dirección funciona)</small>
          <?php endif; ?>
        </label>
        <input type="<?= $esMailtrap ? 'text' : 'email' ?>"
               id="test_to"
               name="test_to"
               class="form-control"
               placeholder="<?= $esMailtrap ? 'test@novaplay.com' : 'destino@ejemplo.com' ?>"
               value="<?= isset($_POST['test_to']) ? e($_POST['test_to']) : ($esMailtrap ? 'test@novaplay.com' : '') ?>"
               required>
      </div>
      <button type="submit" class="btn" style="white-space:nowrap;">
        <i class="fa-solid fa-paper-plane"></i> Enviar prueba
      </button>
    </form>

    <div style="margin-top:var(--space-lg);">
      <a href="index.php" style="color:var(--clr-text-muted);font-size:.85rem;">
        <i class="fa-solid fa-arrow-left"></i> Volver al inicio
      </a>
    </div>

  </div>
</div>
</body>
</html>
