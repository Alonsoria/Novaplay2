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
    "SELECT id_pedido, total, COALESCE(monto_devuelto, 0) AS monto_devuelto,
            estado, fecha, confirmado,
            COALESCE(metodo_pago, '') AS metodo_pago,
            COALESCE(oxxo_referencia, '') AS oxxo_referencia,
            oxxo_expira
     FROM pedidos
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

/* ── Productos y códigos agrupados por pedido ── */
$productosPorPedido = [];
$codigosConfirmados = [];
$confirmadoMap      = [];
foreach ($pedidos as $ped) {
    $confirmadoMap[(int)$ped['id_pedido']] = (int)$ped['confirmado'];
}
/* IDs de pedidos PAGADOS (excluir pendiente_oxxo: sin códigos aún) */
$pedidosPagados = array_filter($pedidos, fn($p) => $p['estado'] !== 'pendiente_oxxo');

if (!empty($pedidosPagados)) {
    $idsPedidos = array_column($pedidosPagados, 'id_pedido');
    $placeholders = implode(',', array_fill(0, count($idsPedidos), '?'));
    $types        = str_repeat('i', count($idsPedidos));
    $stmtCod      = $conn->prepare(
        "SELECT ca.id AS codigo_id, ca.id_pedido, ca.nombre_producto, ca.imagen_producto,
                ca.codigo, ca.confirmado, ca.devuelto,
                COALESCE(p.precio, 0) AS precio,
                COALESCE(GROUP_CONCAT(DISTINCT pp.id_plataforma ORDER BY pp.id_plataforma SEPARATOR ','), '') AS plataformas
         FROM codigos_activacion ca
         LEFT JOIN productos p ON ca.id_producto = p.id_producto
         LEFT JOIN producto_plataforma pp ON ca.id_producto = pp.id_producto
         WHERE ca.id_pedido IN ($placeholders)
         GROUP BY ca.id, ca.id_pedido, ca.nombre_producto, ca.imagen_producto,
                  ca.codigo, ca.confirmado, ca.devuelto, p.precio
         ORDER BY ca.id"
    );
    $stmtCod->bind_param($types, ...$idsPedidos);
    $stmtCod->execute();
    $resCod = $stmtCod->get_result();
    while ($row = $resCod->fetch_assoc()) {
        $pid   = (int)$row['id_pedido'];
        $plats = $row['plataformas'] !== ''
            ? array_map('intval', explode(',', $row['plataformas']))
            : [];
        $itemConf = (int)$row['confirmado'];
        $itemDev  = (int)$row['devuelto'];
        $productosPorPedido[$pid][] = [
            'codigo_id'       => (int)$row['codigo_id'],
            'nombre_producto' => $row['nombre_producto'],
            'imagen_producto' => $row['imagen_producto'],
            'plataformas'     => $plats,
            'precio'          => (float)$row['precio'],
            'confirmado'      => $itemConf,
            'devuelto'        => $itemDev,
            'codigo'          => $itemConf ? $row['codigo'] : null,
        ];
        if ($itemConf) {
            $codigosConfirmados[$pid][] = [
                'nombre_producto' => $row['nombre_producto'],
                'imagen_producto' => $row['imagen_producto'],
                'plataformas'     => $plats,
                'codigo'          => $row['codigo'],
            ];
        }
    }
    $stmtCod->close();
} elseif (!empty($pedidos)) {
    /* Solo pedidos pendiente_oxxo: no hay nada en productosPorPedido */
}

/* ── Mapa de ítems pendientes por pedido ── */
$tienePendientes  = [];
$tieneConfirmados = [];
foreach ($productosPorPedido as $pid => $items) {
    $tienePendientes[$pid]  = false;
    $tieneConfirmados[$pid] = false;
    foreach ($items as $it) {
        if ($it['confirmado'] === 0 && $it['devuelto'] === 0) $tienePendientes[$pid]  = true;
        if ($it['confirmado'] === 1)                          $tieneConfirmados[$pid] = true;
    }
}

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
          <span id="perfil-puntos-val"><?= number_format((int)($user['puntos'] ?? 0)) ?> pts</span>
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
                <th scope="col">Fecha</th>
                <th scope="col">Acción</th>
                <th scope="col">Devolución</th>
              </tr>
            </thead>
            <tbody id="pedidos-tbody">
              <?php
              $numActual = min($totalPedidos, count($pedidos));
              foreach ($pedidos as $ped):
                $pidInt        = (int)$ped['id_pedido'];
                $numDisplay    = $numActual--;
                $conf          = (int)$ped['confirmado'];
                $totalEfectivo = max(0.0, (float)$ped['total'] - (float)$ped['monto_devuelto']);
                $hasPend       = $tienePendientes[$pidInt]  ?? false;
                $hasConf       = $tieneConfirmados[$pidInt] ?? false;
                $expiraTs      = strtotime($ped['fecha']) + 86400;
                $hayDevolucion = (float)$ped['monto_devuelto'] > 0;
                $esOxxoPend    = $ped['estado'] === 'pendiente_oxxo';
                $metodoPago    = $ped['metodo_pago'] ?? '';
              ?>
                <tr data-pedido="<?= $pidInt ?>"
                    data-numero="<?= $numDisplay ?>"
                    data-confirmado="<?= $conf ?>"
                    data-expira="<?= $expiraTs ?>">
                  <td>#<?= $numDisplay ?></td>
                  <td class="pedido-total-td" data-pedido="<?= $pidInt ?>">
                    $<?= number_format($totalEfectivo, 2) ?>
                  </td>
                  <td style="color:var(--clr-text-muted);font-size:.85rem;">
                    <?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?>
                  </td>
                  <td id="accion-td-<?= $pidInt ?>">
                    <?php if ($esOxxoPend): ?>
                      <!-- Pedido pendiente de pago en tienda -->
                      <a href="oxxo_pendiente.php?pedido_id=<?= $pidInt ?>"
                         style="display:inline-flex;align-items:center;gap:5px;
                                background:rgba(13,148,136,.15);border:1px solid rgba(13,148,136,.4);
                                border-radius:6px;padding:5px 10px;color:#2dd4bf;
                                font-size:.8rem;font-weight:600;text-decoration:none;white-space:nowrap;">
                        <i class="fa-solid fa-store" aria-hidden="true"></i> Ver pago en tienda
                      </a>
                    <?php elseif ($hasPend): ?>
                      <button type="button"
                              class="btn-confirmar-prod"
                              data-pedido="<?= $pidInt ?>"
                              data-numero="<?= $numDisplay ?>"
                              data-expira="<?= $expiraTs ?>"
                              style="background:linear-gradient(135deg,var(--clr-accent),var(--clr-accent-2));
                                     border:none;border-radius:6px;padding:5px 10px;
                                     color:#fff;font-size:.8rem;cursor:pointer;white-space:nowrap;">
                        <i class="fa-solid fa-unlock" aria-hidden="true"></i> Confirmar
                      </button>
                    <?php elseif ($hasConf || $ped['estado'] === 'reembolsado' && $conf): ?>
                      <button type="button"
                              class="btn-confirmar-prod"
                              data-pedido="<?= $pidInt ?>"
                              data-numero="<?= $numDisplay ?>"
                              data-expira="<?= $expiraTs ?>"
                              style="background:none;border:1px solid var(--clr-accent);
                                     border-radius:6px;padding:4px 9px;color:var(--clr-accent);
                                     font-size:.78rem;cursor:pointer;white-space:nowrap;">
                        <i class="fa-solid fa-eye" aria-hidden="true"></i> Ver códigos
                      </button>
                    <?php else: ?>
                      <span style="color:var(--clr-border);font-size:.85rem;">—</span>
                    <?php endif; ?>
                  </td>
                  <td id="devolver-td-<?= $pidInt ?>">
                    <?php if ($esOxxoPend): ?>
                      <!-- Pedido aún no pagado; no se puede devolver -->
                      <span style="color:var(--clr-warning);font-size:.8rem;white-space:nowrap;">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i> Pago pendiente
                      </span>
                    <?php elseif ($ped['estado'] === 'reembolsado' && !$hasPend): ?>
                      <span style="color:var(--clr-warning);font-size:.85rem;white-space:nowrap;">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <?= $hasConf ? 'Parcial' : 'Reembolsado' ?>
                      </span>
                    <?php elseif ($hasPend): ?>
                      <?php if ($hayDevolucion): ?>
                        <span style="color:var(--clr-warning);font-size:.78rem;display:block;margin-bottom:3px;white-space:nowrap;">
                          <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Dev. parcial
                        </span>
                      <?php endif; ?>
                      <button type="button"
                              class="btn-devolver"
                              data-pedido="<?= $pidInt ?>"
                              data-numero="<?= $numDisplay ?>"
                              data-metodo="<?= e($metodoPago) ?>"
                              style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);
                                     border-radius:6px;padding:5px 10px;color:var(--clr-warning);
                                     font-size:.8rem;cursor:pointer;white-space:nowrap;">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                        <?= $hayDevolucion ? 'Devolver más' : 'Devolver' ?>
                      </button>
                    <?php else: ?>
                      <span style="color:var(--clr-border);font-size:.85rem;">—</span>
                    <?php endif; ?>
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
          <li style="display:flex;align-items:center;gap:var(--space-md);background:rgba(0,0,0,.7);border:1px solid var(--clr-border);border-radius:var(--radius-md);padding:var(--space-md);">
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

  <!-- ── Modal: solicitar devolución ── -->
  <div id="modal-devolucion"
       role="dialog" aria-modal="true" aria-labelledby="dev-titulo"
       style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);
              z-index:10001;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--clr-surface);border-radius:16px;padding:28px;
                max-width:520px;width:100%;max-height:85vh;overflow-y:auto;
                position:relative;box-shadow:0 0 50px rgba(0,0,0,.6);">

      <button onclick="cerrarModalDevolucion()"
              aria-label="Cerrar"
              style="position:absolute;top:14px;right:18px;background:none;border:none;
                     color:var(--clr-text-muted);font-size:1.4rem;cursor:pointer;line-height:1;">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <h3 id="dev-titulo"
          style="margin-bottom:4px;font-family:var(--font-display);font-size:1.15rem;color:var(--clr-text);">
        <i class="fa-solid fa-rotate-left" style="color:var(--clr-warning);margin-right:8px;" aria-hidden="true"></i>
        Solicitar devolución
      </h3>
      <p id="dev-pedido-ref" style="font-size:.85rem;color:var(--clr-text-muted);margin-bottom:20px;"></p>

      <!-- Paso 1: selección de productos -->
      <div id="dev-step1">
        <p style="font-size:.9rem;color:var(--clr-text-muted);margin-bottom:14px;">
          Selecciona los productos que deseas devolver:
        </p>
        <div id="dev-checkboxes" style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;"></div>
        <div style="display:flex;gap:10px;">
          <button id="dev-btn-continuar"
                  style="flex:1;background:linear-gradient(135deg,var(--clr-accent),var(--clr-accent-2));
                         border:none;border-radius:8px;padding:10px;color:#fff;
                         font-size:.9rem;cursor:pointer;font-weight:600;">
            Continuar
          </button>
          <button onclick="cerrarModalDevolucion()"
                  style="flex:1;background:var(--clr-surface-2);border:1px solid var(--clr-border);
                         border-radius:8px;padding:10px;color:var(--clr-text);font-size:.9rem;cursor:pointer;">
            Cancelar
          </button>
        </div>
      </div>

      <!-- Paso 2: confirmación -->
      <div id="dev-step2" style="display:none;">
        <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);
                    border-radius:10px;padding:18px;margin-bottom:16px;text-align:center;">
          <i class="fa-solid fa-triangle-exclamation"
             style="font-size:2rem;color:var(--clr-warning);margin-bottom:10px;display:block;"
             aria-hidden="true"></i>
          <p style="color:var(--clr-text);font-size:.93rem;line-height:1.6;margin:0 0 6px;font-weight:600;">
            La devolución es inmediata e irreversible. Solo aplica a los productos seleccionados;
            los demás productos del pedido mantienen su estado actual.
          </p>
          <p id="dev-refund-nota" style="color:var(--clr-text-muted);font-size:.84rem;margin:0;">
            El reembolso se procesará en 1–3 días hábiles a tu método de pago original.
          </p>
        </div>
        <div id="dev-resumen" style="margin-bottom:16px;font-size:.85rem;color:var(--clr-text-muted);"></div>
        <div style="display:flex;gap:10px;">
          <button id="dev-btn-confirmar"
                  style="flex:1;background:var(--clr-success, #22c55e);border:none;border-radius:8px;
                         padding:10px;color:#fff;font-size:.9rem;cursor:pointer;font-weight:600;">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Confirmar devolución
          </button>
          <button id="dev-btn-volver"
                  style="flex:1;background:var(--clr-surface-2);border:1px solid var(--clr-border);
                         border-radius:8px;padding:10px;color:var(--clr-text);font-size:.9rem;cursor:pointer;">
            Volver
          </button>
        </div>
      </div>

      <!-- Paso 3: éxito (nunca se destruye, solo se muestra/oculta) -->
      <div id="dev-step-success" style="display:none;">
        <div style="text-align:center;padding:20px 0;">
          <i class="fa-solid fa-circle-check"
             style="font-size:3rem;color:var(--clr-success, #22c55e);margin-bottom:16px;display:block;"
             aria-hidden="true"></i>
          <p style="color:var(--clr-text);font-size:1rem;font-weight:600;margin-bottom:8px;">
            ¡Solicitud registrada!
          </p>
          <p id="dev-success-msg" style="color:var(--clr-text-muted);font-size:.88rem;"></p>
        </div>
      </div>

    </div>
  </div>

  <?php
  $mpProductos = $productosPorPedido;
  $mpCodigos   = $codigosConfirmados;
  require_once '_modal_confirmacion.php';
  ?>

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

/* ── Bindings de perfil para el modal compartido de pedido ── */
(function () {
  /* Callback: actualizar fila de la tabla tras confirmar (y actualizar puntos si la respuesta los trae) */
  window._mpOnConfirm = function (pid, prods, resp) {
    /* Solo actualizar botones/estado de tabla cuando TODOS los ítems están confirmados */
    if (resp && resp.all_done) {
      var row = document.querySelector('tr[data-pedido="' + pid + '"]');
      if (row) { row.dataset.confirmado = '1'; row.style.cursor = 'pointer'; }
      var accionTd = document.getElementById('accion-td-' + pid);
      if (accionTd) {
        accionTd.innerHTML =
          '<button type="button" class="btn-confirmar-prod"' +
          ' data-pedido="' + pid + '"' +
          ' data-numero="' + (row ? row.dataset.numero || '' : '') + '"' +
          ' style="background:none;border:1px solid var(--clr-accent);border-radius:6px;' +
          'padding:4px 9px;color:var(--clr-accent);font-size:.78rem;cursor:pointer;white-space:nowrap;">' +
          '<i class="fa-solid fa-eye"></i> Ver códigos</button>';
      }
      var devolverTd = document.getElementById('devolver-td-' + pid);
      if (devolverTd) {
        devolverTd.innerHTML = '<span style="color:var(--clr-border);font-size:.85rem;">—</span>';
      }
    }
    /* Actualizar puntos si la respuesta los incluye */
    if (resp && resp.puntos !== undefined) {
      var fmt = new Intl.NumberFormat('es-MX').format(resp.puntos);
      var ptsEl = document.getElementById('perfil-puntos-val');
      if (ptsEl) ptsEl.textContent = fmt + ' pts';
      var hdrEl = document.getElementById('header-pts-val');
      if (hdrEl) hdrEl.textContent = fmt + ' puntos';
    }
  };

  /* Event delegation para toda la tabla — clic en filas confirmadas y botones */
  var tbody = document.getElementById('pedidos-tbody');
  if (tbody) {
    tbody.addEventListener('click', function (e) {
      /* Botón "Confirmar productos" o "Ver códigos" (incluyendo los creados dinámicamente) */
      var btnConf = e.target.closest('.btn-confirmar-prod');
      if (btnConf) {
        e.stopPropagation();
        window.abrirModalPedido(parseInt(btnConf.dataset.pedido, 10), btnConf.dataset.numero, btnConf.dataset.expira ? parseInt(btnConf.dataset.expira, 10) : null);
        return;
      }
      /* Clic en la fila de un pedido ya confirmado */
      if (e.target.closest('button')) return;
      var row = e.target.closest('tr');
      if (!row || row.dataset.confirmado !== '1') return;
      window.abrirModalPedido(parseInt(row.dataset.pedido, 10), row.dataset.numero);
    });
  }
})();

/* ── Modal de devolución ── */
(function () {
  const modalDev     = document.getElementById('modal-devolucion');
  const step1        = document.getElementById('dev-step1');
  const step2        = document.getElementById('dev-step2');
  const stepSuccess  = document.getElementById('dev-step-success');
  const devSuccessMsg = document.getElementById('dev-success-msg');
  const checkboxesEl = document.getElementById('dev-checkboxes');
  const pedidoRef    = document.getElementById('dev-pedido-ref');
  const devBtnConf   = document.getElementById('dev-btn-confirmar');

  let activePedidoId   = null;
  let activeNumDisplay = null;
  let selectedIds      = [];   /* IDs de codigos_activacion a devolver */
  let selectedProds    = [];   /* nombres para el resumen visual */

  const devPlatImages = {
    1: 'images/plataformas/xboxlogo.png',
    2: 'images/plataformas/playstationlogo.png',
    3: 'images/plataformas/steamlogo.png',
    4: 'images/plataformas/nintendologo.png',
  };
  const devPlatNames = { 1: 'Xbox', 2: 'PlayStation', 3: 'Steam', 4: 'Nintendo' };

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* Tarjeta de producto con checkbox para el modal de devolución */
  function devProdCard(item) {
    var nombre    = item.nombre_producto;
    var imagen    = item.imagen_producto;
    var plats     = item.plataformas || [];
    var codigoId  = item.codigo_id;
    var precio    = item.precio != null ? parseFloat(item.precio) : null;

    var labelStyle = 'display:flex;align-items:flex-start;gap:12px;cursor:pointer;' +
                     'background:rgba(0,0,0,.7);padding:12px 14px;border-radius:10px;' +
                     'border:1px solid var(--clr-border);transition:border-color .15s;';
    var cbStyle    = 'accent-color:var(--clr-accent);width:16px;height:16px;flex-shrink:0;cursor:pointer;';

    var h = '<label class="mp-prod-card" style="' + labelStyle + '">';
    /* value = ID del registro en codigos_activacion; data-nombre = para el resumen */
    h += '<input type="checkbox" name="dev_prod" value="' + codigoId + '" data-nombre="' + escHtml(nombre) + '" style="' + cbStyle + '">';

    /* Imagen del producto */
    if (imagen) {
      h += '<img src="' + escHtml(imagen) + '" alt="' + escHtml(nombre) + '"' +
           ' style="width:60px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0;">';
    } else {
      h += '<div style="width:56px;height:56px;background:var(--clr-surface);border-radius:6px;' +
           'display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
           '<i class="fa-solid fa-gamepad" style="color:var(--clr-accent);font-size:1.4rem;"></i></div>';
    }

    /* Nombre y plataformas */
    h += '<div style="flex:1;min-width:0;">';
    h += '<div class="mp-prod-name" style="font-weight:600;color:var(--clr-white);font-size:.95rem;' +
         'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(nombre) + '</div>';
    if (plats.length) {
      h += '<div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;margin-top:5px;">';
      plats.forEach(function (pid) {
        var img  = devPlatImages[pid];
        var name = devPlatNames[pid] || ('Plataforma ' + pid);
        if (img) {
          h += '<img src="' + img + '" alt="' + name + '" title="' + name + '"' +
               ' style="height:22px;width:auto;object-fit:contain;opacity:.9;filter:drop-shadow(0 0 2px rgba(0,0,0,.4));">';
        }
      });
      h += '</div>';
    }
    h += '</div>';

    /* Precio al extremo derecho */
    if (precio !== null && !isNaN(precio)) {
      h += '<div class="dev-prod-precio" style="flex-shrink:0;align-self:center;font-weight:700;font-size:.9rem;">' +
           '$' + precio.toFixed(2) + '</div>';
    }

    h += '</label>';
    return h;
  }

  let activeMetodoPago = '';

  function abrir(pedidoId, numDisplay, metodo) {
    activePedidoId   = pedidoId;
    activeNumDisplay = numDisplay;
    activeMetodoPago = metodo || '';
    pedidoRef.textContent = 'Pedido #' + numDisplay;

    /* Ajustar nota de reembolso según método de pago */
    var notaEl = document.getElementById('dev-refund-nota');
    if (notaEl) {
      if (activeMetodoPago === 'oxxo') {
        notaEl.innerHTML =
          '<strong style="color:var(--clr-warning);">Reembolso como puntos Novaplay:</strong> ' +
          'Como el pago fue en tienda (Conekta Efectivo), el importe de los productos seleccionados ' +
          'se acreditará como <strong>puntos de Novaplay</strong> (1 MXN = 1 punto). Esta acción es irreversible.';
      } else {
        notaEl.textContent = 'El reembolso se procesará en 1–3 días hábiles a tu método de pago original.';
      }
    }

    /* Usar datos en tiempo real del modal compartido para reflejar confirmaciones recientes */
    const lista    = typeof window._mpGetProductos === 'function'
      ? window._mpGetProductos(pedidoId)
      : [];

    /* Solo ítems que no han sido confirmados ni devueltos */
    const listaDev = lista.filter(function (item) { return !item.confirmado && !item.devuelto; });

    if (listaDev.length === 0) {
      checkboxesEl.innerHTML =
        '<p style="color:var(--clr-text-muted);font-size:.9rem;padding:8px 0;">' +
        'No hay productos elegibles para devolución en este pedido.</p>';
    } else {
      checkboxesEl.innerHTML = listaDev.map(function (item) {
        return devProdCard(item);
      }).join('');
    }

    /* Resetear estado del modal para permitir múltiples devoluciones */
    devBtnConf.disabled  = false;
    devBtnConf.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Confirmar devolución';
    step1.style.display        = '';
    step2.style.display        = 'none';
    stepSuccess.style.display  = 'none';
    modalDev.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  document.getElementById('dev-btn-continuar').addEventListener('click', function () {
    const checks = [...document.querySelectorAll('[name="dev_prod"]:checked')];
    if (checks.length === 0) {
      alert('Selecciona al menos un producto.');
      return;
    }

    /* IDs para el servidor; nombres para mostrar al usuario */
    selectedIds   = checks.map(function (c) { return parseInt(c.value, 10); });
    selectedProds = checks.map(function (c) { return c.dataset.nombre || c.value; });

    /* Agrupar para el resumen: "Game X × 2" */
    const counts = {};
    selectedProds.forEach(function (n) { counts[n] = (counts[n] || 0) + 1; });
    const resumenItems = Object.entries(counts).map(function (e) {
      return e[1] > 1 ? e[0] + ' × ' + e[1] : e[0];
    });

    const resumen = document.getElementById('dev-resumen');
    resumen.innerHTML =
      '<strong style="color:var(--clr-text);">Productos a devolver:</strong>' +
      '<ul style="margin:6px 0 0 18px;padding:0;">' +
      resumenItems.map(function (p) {
        return '<li style="color:var(--clr-text-muted);font-size:.85rem;margin-top:4px;">' + escHtml(p) + '</li>';
      }).join('') + '</ul>';

    step1.style.display = 'none';
    step2.style.display = '';
  });

  document.getElementById('dev-btn-volver').addEventListener('click', function () {
    step1.style.display = '';
    step2.style.display = 'none';
  });

  document.getElementById('dev-btn-confirmar').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Enviando…';

    fetch('solicitar_devolucion.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'pedido_id='   + encodeURIComponent(activePedidoId) +
               '&codigo_ids=' + encodeURIComponent(JSON.stringify(selectedIds)),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        /* Marcar ítems devueltos en el estado compartido del modal de confirmación */
        if (typeof window._mpMarkDevueltos === 'function') {
          window._mpMarkDevueltos(activePedidoId, selectedIds);
        }

        var accionTd   = document.getElementById('accion-td-'   + activePedidoId);
        var devolverTd = document.getElementById('devolver-td-' + activePedidoId);

        if (data.pendientes > 0) {
          /* Quedan ítems pendientes: dejar el botón "Confirmar" tal como está, solo actualizar Devolver */
          if (devolverTd) {
            devolverTd.innerHTML =
              '<span style="color:var(--clr-warning);font-size:.78rem;display:block;margin-bottom:3px;white-space:nowrap;">' +
              '<i class="fa-solid fa-rotate-left"></i> Dev. parcial</span>' +
              '<button type="button" class="btn-devolver"' +
              ' data-pedido="' + activePedidoId + '"' +
              ' data-numero="' + activeNumDisplay + '"' +
              ' data-metodo="' + escHtml(activeMetodoPago) + '"' +
              ' style="background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);' +
              'border-radius:6px;padding:5px 10px;color:var(--clr-warning);font-size:.8rem;cursor:pointer;white-space:nowrap;">' +
              '<i class="fa-solid fa-rotate-left"></i> Devolver más</button>';
            /* Re-bindear el nuevo botón */
            var newBtn = devolverTd.querySelector('.btn-devolver');
            if (newBtn) {
              newBtn.addEventListener('click', function () {
                abrir(parseInt(newBtn.dataset.pedido, 10), newBtn.dataset.numero, newBtn.dataset.metodo || '');
              });
            }
          }
        } else if (data.confirmados > 0) {
          /* Pedido completado con mix: algunos confirmados, algunos devueltos */
          if (accionTd) {
            accionTd.innerHTML =
              '<button type="button" class="btn-confirmar-prod"' +
              ' data-pedido="' + activePedidoId + '"' +
              ' data-numero="' + activeNumDisplay + '"' +
              ' style="background:none;border:1px solid var(--clr-accent);border-radius:6px;' +
              'padding:4px 9px;color:var(--clr-accent);font-size:.78rem;cursor:pointer;white-space:nowrap;">' +
              '<i class="fa-solid fa-eye"></i> Ver resumen</button>';
          }
          if (devolverTd) {
            devolverTd.innerHTML =
              '<span style="color:var(--clr-warning);font-size:.85rem;white-space:nowrap;">' +
              '<i class="fa-solid fa-rotate-left"></i> Parcial</span>';
          }
          var row = document.querySelector('tr[data-pedido="' + activePedidoId + '"]');
          if (row) { row.dataset.confirmado = '1'; }
        } else {
          /* Devolución total: ningún ítem confirmado */
          if (accionTd) {
            accionTd.innerHTML = '<span style="color:var(--clr-border);font-size:.85rem;">—</span>';
          }
          if (devolverTd) {
            devolverTd.innerHTML =
              '<span style="color:var(--clr-warning);font-size:.88rem;white-space:nowrap;">' +
              '<i class="fa-solid fa-rotate-left"></i> Reembolsado</span>';
          }
        }

        /* Actualizar el total visible en la fila */
        if (data.total_efectivo !== undefined) {
          var totalTd = document.querySelector('.pedido-total-td[data-pedido="' + activePedidoId + '"]');
          if (totalTd) {
            var fmtT = new Intl.NumberFormat('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})
                           .format(data.total_efectivo);
            totalTd.textContent = '$' + fmtT;
          }
        }

        /* Actualizar puntos */
        if (data.puntos !== undefined) {
          var fmt = new Intl.NumberFormat('es-MX').format(data.puntos);
          var ptsEl = document.getElementById('perfil-puntos-val');
          if (ptsEl) ptsEl.textContent = fmt + ' pts';
          var hdrEl = document.getElementById('header-pts-val');
          if (hdrEl) hdrEl.textContent = fmt + ' puntos';
        }

        if (data.oxxo_devolucion) {
          devSuccessMsg.innerHTML =
            '¡Se han acreditado <strong>' + (data.puntos_acreditados || 0) +
            ' puntos</strong> de Novaplay a tu cuenta. ¡Úsalos en tu próxima compra!';
          /* Actualizar display de puntos */
          if (data.puntos !== undefined) {
            var fmt = new Intl.NumberFormat('es-MX').format(data.puntos);
            var ptsEl2 = document.getElementById('perfil-puntos-val');
            if (ptsEl2) ptsEl2.textContent = fmt + ' pts';
            var hdrEl2 = document.getElementById('header-pts-val');
            if (hdrEl2) hdrEl2.textContent = fmt + ' puntos';
          }
        } else {
          devSuccessMsg.textContent = data.pendientes > 0
            ? 'Devolución registrada. Aún tienes ' + data.pendientes + ' producto(s) pendiente(s) en este pedido.'
            : 'Recibirás un correo de confirmación con los detalles de tu devolución.';
        }
        step2.style.display       = 'none';
        stepSuccess.style.display = '';
        setTimeout(function () { cerrarModalDevolucion(); }, 3000);
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Confirmar devolución';
        alert(data.error || 'Ocurrió un error. Intenta de nuevo.');
      }
    })
    .catch(function () {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Confirmar devolución';
      alert('Error de conexión. Intenta de nuevo.');
    });
  });

  window.cerrarModalDevolucion = function () {
    modalDev.style.display    = 'none';
    stepSuccess.style.display = 'none';
    document.body.style.overflow = '';
  };

  modalDev.addEventListener('click', function (e) {
    if (e.target === modalDev) cerrarModalDevolucion();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modalDev.style.display === 'flex') cerrarModalDevolucion();
  });

  document.querySelectorAll('.btn-devolver').forEach(function (btn) {
    btn.addEventListener('click', function () {
      abrir(parseInt(btn.dataset.pedido, 10), btn.dataset.numero, btn.dataset.metodo || '');
    });
  });
})();
</script>

<?php require_once 'footer.php'; ?>
