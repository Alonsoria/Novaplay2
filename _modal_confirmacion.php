<?php
/**
 * NOVAPLAY — _MODAL_CONFIRMACION.PHP  v2
 * Modal de confirmación granular por ítem y visualización de códigos.
 *
 * Variables requeridas:
 *   $mpProductos — array[ pedidoId => [{ codigo_id, nombre_producto, imagen_producto,
 *                                        plataformas[], confirmado, codigo, precio }] ]
 *   $mpCodigos   — array[ pedidoId => [{ nombre_producto, imagen_producto,
 *                                        plataformas[], codigo }] ]
 *
 * API pública (window.*):
 *   abrirModalPedido(pedidoId, label, expiraTs?)  — abre el modal
 *   cerrarModalPedido()                           — cierra el modal
 *   _mpOnConfirm(pedidoId, productos, resp)       — callback post-confirmación
 *   _mpSetCodigos(pedidoId, productos)            — inyecta códigos en runtime
 */
$_mpProductos = $mpProductos ?? [];
$_mpCodigos   = $mpCodigos   ?? [];
?>

<!-- ── Modal compartido: confirmación / detalle de pedido ── -->
<div id="modal-pedido"
     role="dialog" aria-modal="true" aria-labelledby="mp-titulo"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);
            z-index:10000;align-items:center;justify-content:center;padding:16px;">
  <div style="background:var(--clr-surface);border-radius:16px;padding:28px;
              max-width:600px;width:100%;max-height:90vh;overflow-y:auto;
              position:relative;box-shadow:0 0 50px rgba(0,0,0,.6);">

    <button onclick="cerrarModalPedido()"
            aria-label="Cerrar"
            style="position:absolute;top:14px;right:18px;background:none;border:none;
                   color:var(--clr-text-muted);font-size:1.4rem;cursor:pointer;line-height:1;">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <!-- Encabezado con aviso de expiración -->
    <h3 id="mp-titulo"
        style="margin-bottom:2px;font-family:var(--font-display);font-size:1.15rem;color:var(--clr-text);">
      <i class="fa-solid fa-box" style="color:var(--clr-accent);margin-right:8px;" aria-hidden="true"></i>
      Pedido
    </h3>
    <p id="mp-subtitulo" style="font-size:.85rem;color:var(--clr-text-muted);margin-bottom:10px;"></p>

    <!-- Banner 24h: visible solo cuando hay ítems pendientes -->
    <div id="mp-expiry-banner" style="display:none;
         background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.35);
         border-radius:8px;padding:10px 14px;margin-bottom:14px;
         display:none;align-items:center;gap:10px;">
      <i class="fa-solid fa-clock" style="color:var(--clr-warning);font-size:1.1rem;flex-shrink:0;"
         aria-hidden="true"></i>
      <div style="flex:1;min-width:0;">
        <span style="font-size:.82rem;color:var(--clr-warning);font-weight:600;">
          Confirmación automática en:
        </span>
        <span id="mp-countdown"
              style="font-family:monospace;font-size:.82rem;color:var(--clr-warning);
                     margin-left:6px;font-weight:700;"></span>
        <p style="font-size:.76rem;color:var(--clr-text-muted);margin:2px 0 0;">
          Si no confirmas antes, el sistema lo hará automáticamente y los productos
          <strong>ya no podrán ser devueltos</strong>.
        </p>
      </div>
    </div>

    <!-- Lista unificada: pendientes con checkbox + confirmados con código -->
    <div id="mp-step-a">

      <!-- Aviso legal -->
      <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);
                  border-radius:10px;padding:14px 16px;margin-bottom:14px;">
        <p style="color:var(--clr-text);font-size:.87rem;line-height:1.6;margin:0 0 3px;font-weight:600;">
          <i class="fa-solid fa-lock" style="color:var(--clr-accent);margin-right:6px;"
             aria-hidden="true"></i>
          Revisa la plataforma antes de confirmar.
        </p>
        <p style="color:var(--clr-text-muted);font-size:.79rem;margin:0;">
          Al confirmar un producto, <strong>renuncias al derecho de devolución</strong> sobre ese ítem.
          Esta acción es permanente e irreversible.
        </p>
      </div>

      <!-- Seleccionar todos -->
      <div id="mp-sel-todos-wrap"
           style="display:none;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <span style="font-size:.83rem;color:var(--clr-text-muted);">
          Seleccionar para confirmar:
        </span>
        <label style="font-size:.82rem;color:var(--clr-accent-2);cursor:pointer;
                      display:flex;align-items:center;gap:5px;">
          <input type="checkbox" id="mp-check-todos"
                 style="accent-color:var(--clr-accent);width:14px;height:14px;">
          Todos
        </label>
      </div>

      <div id="mp-lista-productos"
           style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>

      <div style="display:flex;gap:10px;">
        <button id="mp-btn-confirmar"
                style="flex:1;background:linear-gradient(135deg,var(--clr-accent),var(--clr-accent-2));
                       border:none;border-radius:8px;padding:10px;color:#fff;
                       font-size:.9rem;cursor:pointer;font-weight:600;">
          <i class="fa-solid fa-unlock" aria-hidden="true"></i> Confirmar seleccionados
        </button>
        <button onclick="cerrarModalPedido()"
                style="flex:1;background:var(--clr-surface-2);border:1px solid var(--clr-border);
                       border-radius:8px;padding:10px;color:var(--clr-text);font-size:.9rem;cursor:pointer;">
          Ahora no
        </button>
      </div>
    </div>

    <!-- Step B: todos confirmados — solo muestra códigos -->
    <div id="mp-step-b" style="display:none;">
      <div id="mp-lista-codigos"
           style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>
      <div style="text-align:center;">
        <button onclick="cerrarModalPedido()"
                style="background:var(--clr-surface-2);border:1px solid var(--clr-border);
                       border-radius:8px;padding:10px 24px;color:var(--clr-text);
                       font-size:.9rem;cursor:pointer;">
          Cerrar
        </button>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  /* ─── Datos ─── */
  const _productos      = <?= json_encode($_mpProductos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const _codigosInic    = <?= json_encode($_mpCodigos,   JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const _codigosRuntime = {};

  /* ─── Refs DOM ─── */
  const modal       = document.getElementById('modal-pedido');
  const titulo      = document.getElementById('mp-titulo');
  const subtit      = document.getElementById('mp-subtitulo');
  const stepA       = document.getElementById('mp-step-a');
  const stepB       = document.getElementById('mp-step-b');
  const listaPrd    = document.getElementById('mp-lista-productos');
  const listaCod    = document.getElementById('mp-lista-codigos');
  const btnConf     = document.getElementById('mp-btn-confirmar');
  const expiryBan   = document.getElementById('mp-expiry-banner');
  const countdown   = document.getElementById('mp-countdown');
  const selTodosWrp = document.getElementById('mp-sel-todos-wrap');
  const checkTodos  = document.getElementById('mp-check-todos');

  let activePedidoId = null;
  let countdownTimer = null;

  /* ─── Helpers ─── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function fmtCode(code) { return String(code).replace(/(.{4})/g, '$1-').replace(/-$/, ''); }

  const platImages = {
    1: 'images/plataformas/xboxlogo.png',
    2: 'images/plataformas/playstationlogo.png',
    3: 'images/plataformas/steamlogo.png',
    4: 'images/plataformas/nintendologo.png',
  };
  const platNames = { 1: 'Xbox', 2: 'PlayStation', 3: 'Steam', 4: 'Nintendo' };

  /* Tarjeta con checkbox (pendiente) */
  function pendCard(item) {
    var labelStyle =
      'display:flex;align-items:flex-start;gap:12px;cursor:pointer;' +
      'background:rgba(0,0,0,.7);padding:12px 14px;border-radius:10px;' +
      'border:1px solid var(--clr-border);transition:border-color .15s;';

    var h = '<label class="mp-prod-card" style="' + labelStyle + '">';
    h += '<input type="checkbox" class="mp-item-check" value="' + item.codigo_id + '"' +
         ' style="accent-color:var(--clr-accent);width:16px;height:16px;' +
         'flex-shrink:0;cursor:pointer;margin-top:4px;">';

    if (item.imagen_producto) {
      h += '<img src="' + escHtml(item.imagen_producto) + '" alt="' + escHtml(item.nombre_producto) + '"' +
           ' style="width:60px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0;">';
    } else {
      h += '<div style="width:56px;height:56px;background:var(--clr-surface);border-radius:6px;' +
           'display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
           '<i class="fa-solid fa-gamepad" style="color:var(--clr-accent);font-size:1.4rem;"></i></div>';
    }

    h += '<div style="flex:1;min-width:0;">';
    h += '<div class="mp-prod-name" style="font-weight:600;color:var(--clr-white);font-size:.95rem;' +
         'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(item.nombre_producto) + '</div>';

    if (item.plataformas && item.plataformas.length) {
      h += '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:7px;">';
      item.plataformas.forEach(function (pid) {
        var img  = platImages[pid];
        var name = platNames[pid] || ('Plataforma ' + pid);
        if (img) {
          h += '<img src="' + img + '" alt="' + name + '" title="' + name + '"' +
               ' style="height:22px;width:auto;object-fit:contain;opacity:.9;' +
               'filter:drop-shadow(0 0 2px rgba(0,0,0,.4));">';
        }
      });
      h += '</div>';
    }
    h += '</div>';

    if (item.precio > 0) {
      h += '<span class="dev-prod-precio" style="flex-shrink:0;align-self:center;font-weight:700;font-size:.88rem;">' +
           '$' + Number(item.precio).toFixed(2) + '</span>';
    }

    h += '</label>';
    return h;
  }

  /* Tarjeta de ítem devuelto (solo lectura) */
  function devueltoCard(item) {
    var h = '<div class="mp-prod-card" style="display:flex;align-items:flex-start;gap:12px;' +
            'background:rgba(245,158,11,.05);border:1px solid rgba(245,158,11,.2);' +
            'border-radius:10px;padding:12px 14px;">';

    if (item.imagen_producto) {
      h += '<img src="' + escHtml(item.imagen_producto) + '" alt="' + escHtml(item.nombre_producto) + '"' +
           ' style="width:60px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0;opacity:.6;">';
    } else {
      h += '<div style="width:56px;height:56px;background:var(--clr-surface);border-radius:6px;' +
           'display:flex;align-items:center;justify-content:center;flex-shrink:0;opacity:.6;">' +
           '<i class="fa-solid fa-gamepad" style="color:var(--clr-accent);font-size:1.4rem;"></i></div>';
    }

    h += '<div style="flex:1;min-width:0;">';
    h += '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">';
    h += '<i class="fa-solid fa-rotate-left" style="color:var(--clr-warning);font-size:.85rem;" aria-hidden="true"></i>';
    h += '<span style="font-weight:600;color:var(--clr-text-muted);font-size:.93rem;">' +
         escHtml(item.nombre_producto) + '</span>';
    h += '<span style="font-size:.72rem;background:rgba(245,158,11,.15);color:var(--clr-warning);' +
         'border:1px solid rgba(245,158,11,.3);border-radius:4px;padding:1px 6px;white-space:nowrap;">' +
         'Devuelto</span>';
    h += '</div>';

    if (item.plataformas && item.plataformas.length) {
      h += '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">';
      item.plataformas.forEach(function (pid) {
        var img  = platImages[pid];
        var name = platNames[pid] || ('Plataforma ' + pid);
        if (img) {
          h += '<img src="' + img + '" alt="' + name + '" title="' + name + '"' +
               ' style="height:20px;width:auto;object-fit:contain;opacity:.5;">';
        }
      });
      h += '</div>';
    }

    h += '</div></div>';
    return h;
  }

  /* Tarjeta de código (ya confirmado — solo lectura) */
  function codeCard(item) {
    var h = '<div class="mp-prod-card" style="display:flex;align-items:flex-start;gap:12px;' +
            'background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.25);' +
            'border-radius:10px;padding:12px 14px;">';

    if (item.imagen_producto) {
      h += '<img src="' + escHtml(item.imagen_producto) + '" alt="' + escHtml(item.nombre_producto) + '"' +
           ' style="width:60px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0;">';
    } else {
      h += '<div style="width:56px;height:56px;background:var(--clr-surface);border-radius:6px;' +
           'display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
           '<i class="fa-solid fa-gamepad" style="color:var(--clr-accent);font-size:1.4rem;"></i></div>';
    }

    h += '<div style="flex:1;min-width:0;">';
    h += '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">';
    h += '<i class="fa-solid fa-circle-check" style="color:var(--clr-success);font-size:.85rem;" aria-hidden="true"></i>';
    h += '<span class="mp-prod-name" style="font-weight:600;color:var(--clr-white);font-size:.93rem;">' +
         escHtml(item.nombre_producto) + '</span>';
    h += '</div>';

    if (item.plataformas && item.plataformas.length) {
      h += '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:8px;">';
      item.plataformas.forEach(function (pid) {
        var img  = platImages[pid];
        var name = platNames[pid] || ('Plataforma ' + pid);
        if (img) {
          h += '<img src="' + img + '" alt="' + name + '" title="' + name + '"' +
               ' style="height:20px;width:auto;object-fit:contain;opacity:.9;">';
        }
      });
      h += '</div>';
    }

    if (item.codigo) {
      h += '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">' +
           '<span class="mp-prod-code" style="font-family:monospace;color:var(--clr-neon);font-size:.95rem;letter-spacing:1px;">' +
           escHtml(fmtCode(item.codigo)) + '</span>' +
           '<button data-raw="' + escHtml(item.codigo) + '" class="btn-copiar-cod"' +
           ' style="background:none;border:1px solid var(--clr-border);border-radius:6px;' +
           'padding:3px 10px;cursor:pointer;color:var(--clr-text);font-size:.8rem;">' +
           '<i class="fa-solid fa-copy"></i> Copiar</button></div>';
    }

    h += '</div></div>';
    return h;
  }

  function attachCopy(container) {
    container.querySelectorAll('.btn-copiar-cod').forEach(function (btn) {
      btn.addEventListener('click', function () {
        navigator.clipboard.writeText(btn.dataset.raw).then(function () {
          btn.innerHTML         = '<i class="fa-solid fa-check"></i> Copiado';
          btn.style.color       = 'var(--clr-success)';
          btn.style.borderColor = 'var(--clr-success)';
          setTimeout(function () {
            btn.innerHTML         = '<i class="fa-solid fa-copy"></i> Copiar';
            btn.style.color       = btn.style.borderColor = '';
          }, 2000);
        });
      });
    });
  }

  /* ─── Countdown timer ─── */
  function startCountdown(expiraTs) {
    clearInterval(countdownTimer);
    function tick() {
      var diff = Math.max(0, expiraTs - Math.floor(Date.now() / 1000));
      if (diff === 0) {
        countdown.textContent = 'Expirado — confirmación automática aplicada.';
        clearInterval(countdownTimer);
        return;
      }
      var h   = Math.floor(diff / 3600);
      var m   = Math.floor((diff % 3600) / 60);
      var s   = diff % 60;
      countdown.textContent = String(h).padStart(2, '0') + ':' +
                              String(m).padStart(2, '0') + ':' +
                              String(s).padStart(2, '0');
    }
    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  /* ─── Renderizar step B (todos los ítems resueltos: confirmados + devueltos) ─── */
  function renderStepB(pedidoId) {
    var allItems  = _productos[pedidoId] || [];
    var confirmed = allItems.filter(function (i) { return i.confirmado; });
    var devueltos = allItems.filter(function (i) { return i.devuelto; });

    /* Preferir runtime (tiene códigos post-confirmación) sobre inic */
    var codigosData = _codigosRuntime[pedidoId] || _codigosInic[pedidoId];

    var html = '';
    if (confirmed.length) {
      html += '<p style="font-size:.78rem;color:var(--clr-text-muted);margin:0 0 8px;' +
              'text-transform:uppercase;letter-spacing:.04em;">' +
              '<i class="fa-solid fa-circle-check" style="color:var(--clr-success);margin-right:5px;"></i>' +
              'Códigos de activación</p>';
      /* Usar codigosData cuando existen (tienen campo codigo seguro), si no usar _productos */
      var codItems = codigosData && codigosData.length ? codigosData : confirmed;
      html += codItems.map(codeCard).join('');
    }
    if (devueltos.length) {
      html += '<div style="margin-top:' + (confirmed.length ? '14px' : '0') +
              ';padding-top:' + (confirmed.length ? '12px' : '0') +
              ';border-top:' + (confirmed.length ? '1px solid var(--clr-border)' : 'none') + ';">';
      html += '<p style="font-size:.78rem;color:var(--clr-text-muted);margin:0 0 8px;' +
              'text-transform:uppercase;letter-spacing:.04em;">' +
              '<i class="fa-solid fa-rotate-left" style="color:var(--clr-warning);margin-right:5px;"></i>' +
              'Productos devueltos</p>';
      html += '<div style="display:flex;flex-direction:column;gap:10px;">';
      html += devueltos.map(devueltoCard).join('');
      html += '</div>';
      html += '</div>';
    }
    if (!html) {
      html = '<p style="color:var(--clr-text-muted);">Sin productos.</p>';
    }

    listaCod.innerHTML = html;
    attachCopy(listaCod);
    expiryBan.style.display = 'none';
    clearInterval(countdownTimer);
    stepA.style.display = 'none';
    stepB.style.display = '';
  }

  /* ─── Renderizar step A (confirmación con checkboxes) ─── */
  function renderStepA(pedidoId, expiraTs) {
    var allItems  = _productos[pedidoId] || [];
    var pending   = allItems.filter(function (i) { return !i.confirmado && !i.devuelto; });
    var confirmed = allItems.filter(function (i) { return i.confirmado; });
    var devueltos = allItems.filter(function (i) { return i.devuelto; });

    var html = '';
    if (pending.length) {
      html += pending.map(pendCard).join('');
    }

    if (confirmed.length) {
      html += '<div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--clr-border);">';
      html += '<p style="font-size:.78rem;color:var(--clr-text-muted);margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">Ya confirmados</p>';
      html += confirmed.map(codeCard).join('');
      html += '</div>';
    }

    if (devueltos.length) {
      html += '<div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--clr-border);">';
      html += '<p style="font-size:.78rem;color:var(--clr-text-muted);margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">Devueltos</p>';
      html += '<div style="display:flex;flex-direction:column;gap:10px;">';
      html += devueltos.map(devueltoCard).join('');
      html += '</div>';
      html += '</div>';
    }

    listaPrd.innerHTML = html || '<p style="color:var(--clr-text-muted);">Sin productos.</p>';
    attachCopy(listaPrd);

    selTodosWrp.style.display = pending.length ? 'flex' : 'none';
    btnConf.style.display     = pending.length ? '' : 'none';

    if (pending.length && expiraTs) {
      expiryBan.style.display = 'flex';
      startCountdown(expiraTs);
    } else {
      expiryBan.style.display = 'none';
      clearInterval(countdownTimer);
    }

    stepA.style.display = '';
    stepB.style.display = 'none';
  }

  /* ─── API pública ─── */

  window.abrirModalPedido = function (pedidoId, label, expiraTs) {
    activePedidoId = pedidoId;
    titulo.innerHTML =
      '<i class="fa-solid fa-box" style="color:var(--clr-accent);margin-right:8px;"></i>Pedido #' + label;

    var allItems  = _productos[pedidoId] || [];
    var hasPending = allItems.some(function (i) { return !i.confirmado && !i.devuelto; });

    if (!hasPending) {
      /* Todos los ítems están confirmados o devueltos → step B */
      var confirmed = allItems.filter(function (i) { return i.confirmado; });
      var devueltos = allItems.filter(function (i) { return i.devuelto; });
      subtit.textContent = confirmed.length && devueltos.length
        ? 'Pedido completado'
        : confirmed.length ? 'Tus códigos de activación' : 'Pedido reembolsado';
      renderStepB(pedidoId);
    } else {
      subtit.textContent = 'Selecciona los productos que quieres confirmar';
      btnConf.disabled  = false;
      btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar seleccionados';
      renderStepA(pedidoId, expiraTs || null);
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.cerrarModalPedido = function () {
    clearInterval(countdownTimer);
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  window._mpSetCodigos = function (pedidoId, productos) {
    _codigosRuntime[pedidoId] = productos;
  };

  window._mpGetProductos = function (pedidoId) {
    return (_productos[pedidoId] || []).slice();
  };

  window._mpMarkDevueltos = function (pedidoId, ids) {
    var allI = _productos[pedidoId] || [];
    allI.forEach(function (item) {
      if (ids.indexOf(item.codigo_id) !== -1) item.devuelto = 1;
    });
  };

  /* ─── Checkbox "Todos" ─── */
  checkTodos.addEventListener('change', function () {
    listaPrd.querySelectorAll('.mp-item-check').forEach(function (cb) {
      cb.checked = checkTodos.checked;
    });
  });

  /* ─── Confirmación AJAX ─── */
  btnConf.addEventListener('click', function () {
    var checks = [...listaPrd.querySelectorAll('.mp-item-check:checked')];
    if (checks.length === 0) {
      alert('Selecciona al menos un producto para confirmar.');
      return;
    }

    var ids = checks.map(function (cb) { return parseInt(cb.value, 10); });

    btnConf.disabled  = true;
    btnConf.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirmando…';

    fetch('confirmar_pedido.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'pedido_id=' + encodeURIComponent(activePedidoId) +
               '&codigo_ids=' + encodeURIComponent(JSON.stringify(ids)),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        /* Actualizar _productos localmente: marcar confirmados y asignar códigos */
        var allItems     = _productos[activePedidoId] || [];
        var matchedCodes = data.productos.slice();
        allItems.forEach(function (item) {
          if (ids.indexOf(item.codigo_id) !== -1) {
            item.confirmado = 1;
            var match = matchedCodes.shift();
            if (match) item.codigo = match.codigo;
          }
        });
        /* Acumular en codigosRuntime para step B */
        var prevCods = _codigosRuntime[activePedidoId] || _codigosInic[activePedidoId] || [];
        _codigosRuntime[activePedidoId] = prevCods.concat(data.productos);

        if (data.all_done) {
          /* No quedan pendientes → step B (muestra códigos + devueltos) */
          var confirmed = allItems.filter(function (i) { return i.confirmado; });
          var devueltos = allItems.filter(function (i) { return i.devuelto; });
          subtit.textContent = confirmed.length && devueltos.length
            ? 'Pedido completado'
            : 'Tus códigos de activación';
          renderStepB(activePedidoId);
        } else {
          /* Quedan ítems pendientes → re-renderizar step A */
          btnConf.disabled  = false;
          btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar seleccionados';
          checkTodos.checked = false;
          renderStepA(activePedidoId, null);
          subtit.textContent = 'Confirmados ' + ids.length + ' ítem(s). Quedan ' + data.remaining + ' pendiente(s).';
        }

        /* Notificar callback externo */
        if (typeof window._mpOnConfirm === 'function') {
          window._mpOnConfirm(activePedidoId, data.productos, data);
        }
      } else {
        btnConf.disabled  = false;
        btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar seleccionados';
        alert(data.error || 'Error al confirmar. Inténtalo de nuevo.');
      }
    })
    .catch(function () {
      btnConf.disabled  = false;
      btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar seleccionados';
      alert('Error de conexión. Inténtalo de nuevo.');
    });
  });

  /* ─── Cerrar ─── */
  modal.addEventListener('click', function (e) {
    if (e.target === modal) cerrarModalPedido();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') cerrarModalPedido();
  });
})();
</script>
