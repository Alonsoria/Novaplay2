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

    /* Total de pedidos del usuario (para numeración #1, #2… igual que perfil) */
    $totalPedidosNotif = 0;
    try {
        $resCnt = $conn->query("SELECT COUNT(*) AS c FROM pedidos WHERE id_usuario = $uid");
        if ($resCnt) $totalPedidosNotif = (int)($resCnt->fetch_assoc()['c'] ?? 0);
    } catch (mysqli_sql_exception $e) {}

    /* Códigos de los pedidos recientes (para "Ver detalle" en el panel) */
    $codigosNotif = [];
    if (!empty($pedidosRecientes)) {
        try {
            $pids = array_column($pedidosRecientes, 'id_pedido');
            $phs  = implode(',', array_fill(0, count($pids), '?'));
            $stmtCN = $conn->prepare(
                "SELECT id_pedido, nombre_producto, codigo
                 FROM codigos_activacion
                 WHERE id_pedido IN ($phs)
                 ORDER BY id"
            );
            $stmtCN->bind_param(str_repeat('i', count($pids)), ...$pids);
            $stmtCN->execute();
            $rCN = $stmtCN->get_result();
            while ($r = $rCN->fetch_assoc()) {
                $codigosNotif[$r['id_pedido']][] = $r;
            }
            $stmtCN->close();
        } catch (mysqli_sql_exception $e) {}
    }

    /* Datos para el tooltip del avatar (email + puntos) */
    $headerUserInfo = ['email' => '', 'puntos' => 0];
    try {
        $stmtUI = $conn->prepare("SELECT email, puntos FROM usuarios WHERE id_usuario = ?");
        $stmtUI->bind_param("i", $uid);
        $stmtUI->execute();
        $headerUserInfo = $stmtUI->get_result()->fetch_assoc() ?? $headerUserInfo;
        $stmtUI->close();
    } catch (Exception $e) {}
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
  <link rel="stylesheet" href="style.css">
  <!-- animate.css — animaciones de entrada/salida en clases CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <!-- AOS — Animate On Scroll -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <style>
    /* ── Tooltips del sidebar ── */
    .sidebar-icon[data-tooltip] { position: relative; }
    .sidebar-icon[data-tooltip]::after {
      content: attr(data-tooltip);
      position: absolute;
      left: calc(100% + 14px);
      top: 50%;
      transform: translateY(-50%) translateX(-6px);
      background: #1a1a2e;
      color: #fff;
      padding: 5px 12px;
      border-radius: 8px;
      font-size: .78rem;
      font-weight: 600;
      white-space: nowrap;
      pointer-events: none;
      opacity: 0;
      border: 1px solid rgba(255,255,255,.12);
      box-shadow: 0 4px 16px rgba(0,0,0,.4);
      transition: opacity .18s ease, transform .18s ease;
      z-index: 200;
    }
    .sidebar-icon[data-tooltip]:hover::after {
      opacity: 1;
      transform: translateY(-50%) translateX(0);
    }

    /* ── Tooltip / card de la cuenta en el header ── */
    .user-card-wrap {
      position: relative;
      display: inline-flex;
      align-items: center;
    }
    .user-card-wrap .user-hover-card {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      min-width: 200px;
      background: #1a1a2e;
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 12px;
      padding: 14px 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
      opacity: 0;
      pointer-events: none;
      transform: translateY(-6px);
      transition: opacity .2s ease, transform .2s ease;
      z-index: 500;
      text-align: left;
    }
    .user-card-wrap:hover .user-hover-card {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }
    .user-hover-card__name {
      font-weight: 700;
      font-size: .95rem;
      color: #fff;
      margin-bottom: 4px;
    }
    .user-hover-card__email {
      font-size: .78rem;
      color: rgba(255,255,255,.55);
      word-break: break-all;
      margin-bottom: 10px;
    }
    .user-hover-card__pts {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: .82rem;
      font-weight: 600;
      color: #a78bfa;
      border-top: 1px solid rgba(255,255,255,.1);
      padding-top: 8px;
    }
  </style>
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
      <!-- Avatar con hover card de cuenta -->
      <div class="user-card-wrap">
        <a href="perfil.php" class="site-header__user" title="Mi perfil">
          <span class="user-avatar" aria-label="Perfil: <?= e($_SESSION['username'] ?? 'U') ?>">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
          </span>
        </a>
        <div class="user-hover-card" role="tooltip" aria-hidden="true">
          <div class="user-hover-card__name">
            <?= e($_SESSION['username'] ?? 'Usuario') ?>
          </div>
          <div class="user-hover-card__email">
            <?= e($headerUserInfo['email'] ?? '') ?>
          </div>
          <div class="user-hover-card__pts">
            <i class="fa-solid fa-star" style="color:#facc15;" aria-hidden="true"></i>
            <?= number_format((int)($headerUserInfo['puntos'] ?? 0)) ?> puntos
          </div>
        </div>
      </div><!-- /.user-card-wrap -->
      <a href="logout_ajax.php" title="Cerrar sesión">
        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
      </a>
    <?php else: ?>
      <a href="login.php">Iniciar sesión</a>
      <a href="signup.php" class="btn btn--outline" style="padding:6px 16px;font-size:.85rem;">Registrarse</a>
    <?php endif; ?>
  </nav>

  <!-- Acciones rápidas (derecha del header, solo en móvil) -->
  <div class="site-header__actions">
    <a href="carrito.php" class="site-header__action-btn site-header__action-cart" aria-label="Carrito" style="position:relative;">
      <i class="fa-solid fa-cart-shopping"></i>
      <?php if ($cartCount > 0): ?>
        <span class="cart-count-badge"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <?php if (is_logged_in()): ?>
      <a href="perfil.php" class="site-header__action-btn" aria-label="Mi Perfil">
        <span class="user-avatar" style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--clr-white);background:var(--clr-accent);font-size:1rem;font-weight:700;flex-shrink:0;">
          <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </span>
      </a>
    <?php else: ?>
      <a href="login.php" class="site-header__action-btn" aria-label="Iniciar sesión">
        <i class="fa-solid fa-right-to-bracket"></i>
      </a>
    <?php endif; ?>
  </div>

  <!-- Botón hamburguesa (mobile) -->
  <button class="site-header__burger" id="burgerBtn"
          aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
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
<aside class="sidebar" id="sidebar" aria-label="Navegación lateral">

  <!-- Botón cerrar (solo visible en off-canvas / móvil) -->
  <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Cerrar menú lateral">
    <i class="fa-solid fa-xmark"></i>
  </button>

  <a href="index.php" class="sidebar-icon" title="Inicio" data-tooltip="Inicio" aria-label="Inicio">
    <i class="fa-solid fa-house"></i>
  </a>
  <a href="productos.php" class="sidebar-icon" title="Juegos" data-tooltip="Juegos" aria-label="Catálogo">
    <i class="fa-solid fa-gamepad"></i>
  </a>
  <a href="combos.php" class="sidebar-icon" title="Combos" data-tooltip="Combos" aria-label="Combos">
    <i class="fa-solid fa-layer-group"></i>
  </a>
  <a href="carrito.php" class="sidebar-icon" title="Carrito" data-tooltip="Carrito" aria-label="Carrito" id="sidebarCartLink">
    <i class="fa-solid fa-cart-shopping"></i>
    <?php if ($cartCount > 0): ?>
      <span class="cart-count-badge" id="cartBadge"><?= $cartCount ?></span>
    <?php else: ?>
      <span class="cart-count-badge d-none" id="cartBadge">0</span>
    <?php endif; ?>
  </a>

  <?php if (is_logged_in()): ?>
    <a href="recompensaDiaria.php" class="sidebar-icon" title="Recompensa Diaria" data-tooltip="Recompensa" aria-label="Recompensa">
      <i class="fa-solid fa-gift"></i>
    </a>
    <a href="perfil.php" class="sidebar-icon" title="Mi Perfil" data-tooltip="Mi Perfil" aria-label="Perfil">
      <i class="fa-solid fa-user"></i>
    </a>
  <?php endif; ?>

  <!-- ── Separador ── -->
  <div class="sidebar-divider"></div>

  <!-- ── Icono de notificaciones ── -->
  <button class="sidebar-icon sidebar-notif-btn" id="notifBtn"
          title="Notificaciones" data-tooltip="Notificaciones" aria-label="Ver notificaciones"
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
        <?php
          /* Mismo esquema que perfil.php: el pedido más reciente recibe el número
             más alto, bajando hasta #1 para el más antiguo del lote. */
          $numActualNotif = min($totalPedidosNotif, count($pedidosRecientes));
          foreach ($pedidosRecientes as $ped):
            $pedId      = (int)$ped['id_pedido'];
            $numDisplay = $numActualNotif--;
            $hasCods    = !empty($codigosNotif[$pedId]);
        ?>
          <li class="notif-item" style="flex-direction:column;align-items:flex-start;gap:6px;">
            <div style="display:flex;align-items:center;gap:10px;width:100%;">
              <div class="notif-item__icon">
                <i class="fa-solid fa-<?= $ped['estado'] === 'pagado' ? 'circle-check' : 'clock' ?>"
                   style="color:var(--clr-<?= $ped['estado'] === 'pagado' ? 'success' : 'warning' ?>);"></i>
              </div>
              <div class="notif-item__body" style="flex:1;">
                <span class="notif-item__title">Pedido #<?= $numDisplay ?></span>
                <span class="notif-item__meta">
                  $<?= number_format((float)$ped['total'], 2) ?> —
                  <span style="color:var(--clr-<?= $ped['estado'] === 'pagado' ? 'success' : 'warning' ?>);">
                    <?= ucfirst(e($ped['estado'])) ?>
                  </span>
                </span>
                <span class="notif-item__date"><?= date('d/m/Y', strtotime($ped['fecha'])) ?></span>
              </div>
              <?php if ($hasCods): ?>
                <button type="button"
                        class="notif-detalle-btn"
                        data-pedido="<?= $pedId ?>"
                        aria-expanded="false"
                        style="background:none;border:1px solid var(--clr-border);border-radius:6px;
                               padding:4px 8px;font-size:.75rem;color:var(--clr-text-muted);
                               cursor:pointer;white-space:nowrap;flex-shrink:0;">
                  <i class="fa-solid fa-eye" aria-hidden="true"></i> Ver
                </button>
              <?php endif; ?>
            </div>

            <?php if ($hasCods): ?>
              <!-- Detalle expandible de códigos -->
              <div id="notif-det-<?= $pedId ?>"
                   style="display:none;width:100%;padding-left:34px;">
                <ul style="list-style:none;margin:4px 0 0;padding:0;display:flex;flex-direction:column;gap:6px;">
                  <?php foreach ($codigosNotif[$pedId] as $cod):
                    $fmt = implode('-', str_split($cod['codigo'], 4));
                  ?>
                    <li style="background:var(--clr-surface-2);border-radius:8px;padding:8px 10px;">
                      <div style="font-size:.78rem;color:var(--clr-text-muted);margin-bottom:3px;">
                        <?= e($cod['nombre_producto']) ?>
                      </div>
                      <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-family:monospace;color:var(--clr-neon);font-size:.85rem;letter-spacing:.5px;">
                          <?= e($fmt) ?>
                        </span>
                        <button type="button"
                                data-raw="<?= e($cod['codigo']) ?>"
                                class="notif-copy-btn"
                                style="background:none;border:none;cursor:pointer;
                                       color:var(--clr-text-muted);font-size:.75rem;padding:0;"
                                title="Copiar código">
                          <i class="fa-solid fa-copy" aria-hidden="true"></i>
                        </button>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="perfil.php#pedidos" class="notif-ver-todos">Ver todos los pedidos</a>
    <?php endif; ?>
  </div>

</aside>

<!-- Overlay para sidebar off-canvas -->
<div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true"></div>

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
