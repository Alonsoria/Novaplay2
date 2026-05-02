<?php
/**
 * NOVAPLAY — HEADER.PHP
 * Cabecera global del sitio. Incluir al inicio de cada página.
 * Requiere que $pageTitle esté definida antes de incluir este archivo.
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/config.php';

$pageTitle = isset($pageTitle) ? e($pageTitle) : 'NovaPlay';

/* Conteo de items en carrito (para el badge) */
$cartCount = 0;
if (is_logged_in()) {
    $uid = (int)$_SESSION['user_id'];
    try {
        $resCart = $conn->query("SELECT SUM(cantidad) AS total FROM carrito WHERE id_usuario = $uid");
        if ($resCart) {
            $cartCount = (int)($resCart->fetch_assoc()['total'] ?? 0);
        }
    } catch (mysqli_sql_exception $e) {
        $cartCount = 0;
    }

    /* Conteo notificaciones no leídas */
    $notifCount = 0;
    try {
        $resNotif = $conn->query("SELECT COUNT(*) AS cnt FROM notificaciones WHERE id_usuario = $uid AND leida = 0");
        if ($resNotif) $notifCount = (int)($resNotif->fetch_assoc()['cnt'] ?? 0);
    } catch (mysqli_sql_exception $e) { $notifCount = 0; }

    /* Pedidos recientes para el panel de notificaciones (máx. 5) */
    $pedidosRecientes = [];
    try {
        $resPed = $conn->query(
            "SELECT id_pedido, total, estado, fecha FROM pedidos
             WHERE id_usuario = $uid ORDER BY fecha DESC LIMIT 5"
        );
        if ($resPed) {
            while ($row = $resPed->fetch_assoc()) $pedidosRecientes[] = $row;
        }
    } catch (mysqli_sql_exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="NovaPlay — Tu tienda de videojuegos y suscripciones gaming">
  <title><?= $pageTitle ?> | NovaPlay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <link rel="stylesheet" href="style.css">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
</head>
<body>

<!-- ═══════════════════════════ HEADER ═══════════════════════════ -->
<header class="site-header">

  <!-- Búsqueda — columna izquierda -->
  <div class="site-header__search" role="search">
    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    <input type="search"
           id="search-input"
           placeholder="Buscar juegos, suscripciones..."
           autocomplete="off"
           maxlength="100"
           aria-label="Buscar productos">
  </div>

  <!-- Logo — columna central (solo imagen, sin texto) -->
  <a href="index.php" class="site-header__logo" aria-label="NovaPlay - Inicio">
    <img src="./images/logo.png" alt="NovaPlay" onerror="this.style.display='none'">
  </a>

  <!-- Nav — columna derecha -->
  <nav class="site-header__nav" aria-label="Navegación principal">

    <!-- Dropdown plataformas -->
    <div class="platform-dropdown" id="platformDropdown">
      <button class="platform-dropdown__toggle"
              aria-haspopup="true" aria-expanded="false"
              aria-controls="platformMenu" id="platformToggleBtn">
        <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
        Plataformas
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>
      <div class="platform-dropdown__menu" id="platformMenu" role="menu">
        <button class="platform-dropdown__close" aria-label="Cerrar menú de plataformas" id="platformCloseBtn">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <a href="productos.php?plataforma=PlayStation" role="menuitem"><img src="./images/ps.png" alt=""> PlayStation</a>
        <a href="productos.php?plataforma=Xbox"        role="menuitem"><img src="./images/xbox.png" alt=""> Xbox</a>
        <a href="productos.php?plataforma=Nintendo"    role="menuitem"><img src="./images/nintendo.png" alt=""> Nintendo</a>
        <a href="productos.php?plataforma=PC"          role="menuitem"><img src="./images/pc.png" alt=""> PC</a>
      </div>
    </div>

    <a href="combos.php">Combos</a>

    <?php if (is_logged_in()): ?>
      <a href="recompensaDiaria.php" title="Recompensa Diaria">
        <i class="fa-solid fa-gift" aria-hidden="true"></i>
      </a>
      <!-- Avatar como enlace al perfil -->
      <a href="perfil.php" class="site-header__user" title="Mi perfil">
        <span class="user-avatar" aria-label="Perfil: <?= e($_SESSION['username'] ?? 'U') ?>">
          <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </span>
      </a>
      <a href="logout_ajax.php" title="Cerrar sesión">
        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
      </a>
    <?php else: ?>
      <a href="login.php">Iniciar sesión</a>
      <a href="signup.php" class="btn btn--outline" style="padding:6px 16px;font-size:.85rem;">Registrarse</a>
    <?php endif; ?>
  </nav>

  <!-- Botón hamburguesa (mobile) -->
  <button class="site-header__burger" id="burgerBtn"
          aria-label="Abrir menú" aria-expanded="false" aria-controls="mobileNav">
    <span></span><span></span><span></span>
  </button>

</header>

<!-- Nav mobile -->
<nav class="mobile-nav" id="mobileNav" aria-label="Menú móvil">
  <a href="index.php"><i class="fa-solid fa-house"></i> Inicio</a>
  <a href="productos.php"><i class="fa-solid fa-gamepad"></i> Juegos</a>
  <a href="combos.php"><i class="fa-solid fa-layer-group"></i> Combos</a>
  <?php if (is_logged_in()): ?>
    <a href="carrito.php">
      <i class="fa-solid fa-cart-shopping"></i> Carrito
      <?php if ($cartCount > 0): ?><span class="cart-count-badge"><?= $cartCount ?></span><?php endif; ?>
    </a>
    <a href="recompensaDiaria.php"><i class="fa-solid fa-gift"></i> Recompensa</a>
    <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi Perfil</a>
    <a href="logout_ajax.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
  <?php else: ?>
    <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión</a>
    <a href="signup.php"><i class="fa-solid fa-user-plus"></i> Registrarse</a>
  <?php endif; ?>
</nav>

<!-- ═══════════════════════════ SIDEBAR ═══════════════════════════ -->
<aside class="sidebar" aria-label="Navegación lateral">

  <a href="index.php" class="sidebar-icon" title="Inicio" aria-label="Inicio">
    <i class="fa-solid fa-house"></i>
  </a>
  <a href="productos.php" class="sidebar-icon" title="Juegos" aria-label="Catálogo">
    <i class="fa-solid fa-gamepad"></i>
  </a>
  <a href="combos.php" class="sidebar-icon" title="Combos" aria-label="Combos">
    <i class="fa-solid fa-layer-group"></i>
  </a>
  <a href="carrito.php" class="sidebar-icon" title="Carrito" aria-label="Carrito" id="sidebarCartLink">
    <i class="fa-solid fa-cart-shopping"></i>
    <?php if ($cartCount > 0): ?>
      <span class="cart-count-badge" id="cartBadge"><?= $cartCount ?></span>
    <?php else: ?>
      <span class="cart-count-badge d-none" id="cartBadge">0</span>
    <?php endif; ?>
  </a>

  <?php if (is_logged_in()): ?>
    <a href="recompensaDiaria.php" class="sidebar-icon" title="Recompensa Diaria" aria-label="Recompensa">
      <i class="fa-solid fa-gift"></i>
    </a>
    <a href="perfil.php" class="sidebar-icon" title="Mi Perfil" aria-label="Perfil">
      <i class="fa-solid fa-user"></i>
    </a>
  <?php endif; ?>

  <!-- ── Separador ── -->
  <div class="sidebar-divider"></div>

  <!-- ── Icono de notificaciones ── -->
  <button class="sidebar-icon sidebar-notif-btn" id="notifBtn"
          title="Notificaciones" aria-label="Ver notificaciones"
          aria-expanded="false" aria-controls="notifPanel">
    <i class="fa-solid fa-bell"></i>
    <?php if (!empty($notifCount) && $notifCount > 0): ?>
      <span class="cart-count-badge notif-badge" id="notifBadge"><?= $notifCount ?></span>
    <?php else: ?>
      <span class="cart-count-badge d-none" id="notifBadge">0</span>
    <?php endif; ?>
  </button>

  <!-- ── Panel de notificaciones ── -->
  <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Notificaciones" aria-hidden="true">
    <div class="notif-panel__header">
      <span><i class="fa-solid fa-bell" style="color:var(--clr-accent);margin-right:6px;"></i>Notificaciones</span>
      <button class="notif-panel__close" id="notifClose" aria-label="Cerrar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <?php if (!is_logged_in()): ?>
      <!-- No logueado -->
      <div class="notif-empty">
        <i class="fa-solid fa-user-lock" style="font-size:2rem;color:var(--clr-border);margin-bottom:8px;"></i>
        <p>Inicia sesión para ver tus notificaciones.</p>
        <a href="login.php" class="btn" style="margin-top:var(--space-md);width:100%;text-align:center;font-size:.85rem;">
          <i class="fa-solid fa-right-to-bracket"></i> Iniciar sesión
        </a>
      </div>

    <?php elseif (empty($pedidosRecientes)): ?>
      <!-- Logueado sin pedidos -->
      <div class="notif-empty">
        <i class="fa-solid fa-box-open" style="font-size:2rem;color:var(--clr-border);margin-bottom:8px;"></i>
        <p style="font-weight:600;color:var(--clr-text);margin-bottom:4px;">Aún no hay pedidos por mostrar...</p>
        <p>¡¡Anímate a aumentar tu biblioteca de juegos!!</p>
        <a href="productos.php" class="btn" style="margin-top:var(--space-md);width:100%;text-align:center;font-size:.85rem;">
          <i class="fa-solid fa-gamepad"></i> Ver juegos
        </a>
      </div>

    <?php else: ?>
      <!-- Logueado con pedidos -->
      <ul class="notif-list">
        <?php foreach ($pedidosRecientes as $ped): ?>
          <li class="notif-item">
            <div class="notif-item__icon">
              <i class="fa-solid fa-<?= $ped['estado'] === 'pagado' ? 'circle-check' : 'clock' ?>"
                 style="color:var(--clr-<?= $ped['estado'] === 'pagado' ? 'success' : 'warning' ?>);"></i>
            </div>
            <div class="notif-item__body">
              <span class="notif-item__title">Pedido #<?= (int)$ped['id_pedido'] ?></span>
              <span class="notif-item__meta">
                $<?= number_format((float)$ped['total'], 2) ?> —
                <span style="color:var(--clr-<?= $ped['estado'] === 'pagado' ? 'success' : 'warning' ?>);">
                  <?= ucfirst(e($ped['estado'])) ?>
                </span>
              </span>
              <span class="notif-item__date"><?= date('d/m/Y', strtotime($ped['fecha'])) ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="perfil.php#pedidos" class="notif-ver-todos">Ver todos los pedidos</a>
    <?php endif; ?>
  </div>

</aside>

<!-- ═══════════════════════════ BOTTOM NAV ═══════════════════════════ -->
<nav class="bottom-nav" aria-label="Navegación rápida móvil">
  <a href="index.php" aria-label="Inicio"><i class="fa-solid fa-house"></i> Inicio</a>
  <a href="productos.php" aria-label="Juegos"><i class="fa-solid fa-gamepad"></i> Juegos</a>
  <a href="carrito.php" aria-label="Carrito" style="position:relative;">
    <i class="fa-solid fa-cart-shopping"></i> Carrito
    <?php if ($cartCount > 0): ?><span class="cart-count-badge"><?= $cartCount ?></span><?php endif; ?>
  </a>
  <?php if (is_logged_in()): ?>
    <a href="recompensaDiaria.php" aria-label="Recompensa"><i class="fa-solid fa-gift"></i> Reward</a>
  <?php else: ?>
    <a href="login.php" aria-label="Entrar"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a>
  <?php endif; ?>
</nav>

<div class="page-wrapper">
