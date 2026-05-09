<?php
/**
 * NOVAPLAY — _MODAL_CONFIRMACION.PHP
 * Componente compartido: modal de confirmación de productos y visualización de códigos.
 *
 * Variables requeridas antes de incluir:
 *   $mpProductos — array[ pedidoId => [{ nombre_producto, imagen_producto, plataforma }] ]
 *   $mpCodigos   — array[ pedidoId => [{ nombre_producto, imagen_producto, plataforma, codigo }] ]
 *
 * API pública (window.*) disponible después del include:
 *   abrirModalPedido(pedidoId, label)   — abre el modal
 *   cerrarModalPedido()                 — cierra el modal
 *   _mpOnConfirm(pedidoId, productos)   — callback opcional; defínelo antes de abrir el modal
 *                                         para reaccionar tras una confirmación exitosa
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
              max-width:580px;width:100%;max-height:85vh;overflow-y:auto;
              position:relative;box-shadow:0 0 50px rgba(0,0,0,.6);">

    <button onclick="cerrarModalPedido()"
            aria-label="Cerrar"
            style="position:absolute;top:14px;right:18px;background:none;border:none;
                   color:var(--clr-text-muted);font-size:1.4rem;cursor:pointer;line-height:1;">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <h3 id="mp-titulo"
        style="margin-bottom:4px;font-family:var(--font-display);font-size:1.15rem;color:var(--clr-white);">
      <i class="fa-solid fa-box" style="color:var(--clr-accent);margin-right:8px;" aria-hidden="true"></i>
      Pedido
    </h3>
    <p id="mp-subtitulo" style="font-size:.85rem;color:var(--clr-text-muted);margin-bottom:20px;"></p>

    <!-- Step A: productos sin código — flujo de confirmación -->
    <div id="mp-step-a">
      <div id="mp-lista-productos"
           style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;"></div>

      <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);
                  border-radius:10px;padding:16px;margin-bottom:16px;text-align:center;">
        <i class="fa-solid fa-lock"
           style="font-size:1.6rem;color:var(--clr-accent);margin-bottom:8px;display:block;"
           aria-hidden="true"></i>
        <p style="color:var(--clr-white);font-size:.9rem;line-height:1.6;margin:0 0 4px;">
          Revisa y confirma bien la plataforma de canjeo, ¡no es posible revertir esta acción!
        </p>
        <p style="color:var(--clr-text-muted);font-size:.82rem;margin:0;">
          Una vez confirmado, no podrás solicitar reembolso.
        </p>
      </div>

      <div style="display:flex;gap:10px;">
        <button id="mp-btn-confirmar"
                style="flex:1;background:linear-gradient(135deg,var(--clr-accent),var(--clr-accent-2));
                       border:none;border-radius:8px;padding:10px;color:#fff;
                       font-size:.9rem;cursor:pointer;font-weight:600;">
          <i class="fa-solid fa-unlock" aria-hidden="true"></i> Confirmar y ver códigos
        </button>
        <button onclick="cerrarModalPedido()"
                style="flex:1;background:var(--clr-surface-2);border:1px solid var(--clr-border);
                       border-radius:8px;padding:10px;color:var(--clr-text);font-size:.9rem;cursor:pointer;">
          Ahora no
        </button>
      </div>
    </div>

    <!-- Step B: productos + códigos — pedido ya confirmado -->
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
  const _codigosRuntime = {};     /* pedidos confirmados en esta sesión de página */

  /* ─── Refs DOM ─── */
  const modal    = document.getElementById('modal-pedido');
  const titulo   = document.getElementById('mp-titulo');
  const subtit   = document.getElementById('mp-subtitulo');
  const stepA    = document.getElementById('mp-step-a');
  const stepB    = document.getElementById('mp-step-b');
  const listaPrd = document.getElementById('mp-lista-productos');
  const listaCod = document.getElementById('mp-lista-codigos');
  const btnConf  = document.getElementById('mp-btn-confirmar');

  let activePedidoId = null;

  /* ─── Helpers ─── */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function fmtCode(code) { return code.replace(/(.{4})/g, '$1-').replace(/-$/, ''); }

  const platImages = {
    1: 'images/plataformas/xboxlogo.png',
    2: 'images/plataformas/playstationlogo.png',
    3: 'images/plataformas/steamlogo.png',
    4: 'images/plataformas/nintendologo.png',
  };
  const platNames = { 1: 'Xbox', 2: 'PlayStation', 3: 'Steam', 4: 'Nintendo' };

  /* Tarjeta de producto (reutilizada en step A y step B) */
  function prodCard(item, showCode) {
    let h = '<div style="display:flex;align-items:flex-start;gap:12px;' +
            'background:var(--clr-surface-2);border:1px solid var(--clr-border);' +
            'border-radius:10px;padding:12px 14px;">';

    /* Imagen */
    if (item.imagen_producto) {
      h += '<img src="' + escHtml(item.imagen_producto) + '"' +
           ' alt="' + escHtml(item.nombre_producto) + '"' +
           ' style="width:56px;height:56px;object-fit:cover;border-radius:6px;flex-shrink:0;">';
    } else {
      h += '<div style="width:56px;height:56px;background:var(--clr-surface);border-radius:6px;' +
           'display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
           '<i class="fa-solid fa-gamepad" style="color:var(--clr-accent);font-size:1.4rem;"></i></div>';
    }

    h += '<div style="flex:1;min-width:0;">';

    /* Nombre */
    h += '<div style="font-weight:600;color:var(--clr-white);font-size:.95rem;' +
         'white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
         escHtml(item.nombre_producto) + '</div>';

    /* Logos de plataforma */
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

    /* Código (solo en step B) */
    if (showCode && item.codigo) {
      h += '<div style="display:flex;align-items:center;gap:8px;margin-top:10px;flex-wrap:wrap;">' +
           '<span style="font-family:monospace;color:var(--clr-neon);font-size:.95rem;letter-spacing:1px;">' +
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
          btn.innerHTML     = '<i class="fa-solid fa-check"></i> Copiado';
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

  /* ─── API pública ─── */

  window.abrirModalPedido = function (pedidoId, label) {
    activePedidoId = pedidoId;
    titulo.innerHTML =
      '<i class="fa-solid fa-box" style="color:var(--clr-accent);margin-right:8px;"></i>Pedido #' + label;

    const listaConCod = _codigosInic[pedidoId] || _codigosRuntime[pedidoId];

    if (listaConCod) {
      /* Pedido ya confirmado → mostrar step B con códigos */
      subtit.textContent  = 'Tus códigos de activación';
      listaCod.innerHTML  = listaConCod.map(function (p) { return prodCard(p, true); }).join('') ||
        '<p style="color:var(--clr-text-muted);">Sin productos.</p>';
      attachCopy(listaCod);
      stepA.style.display = 'none';
      stepB.style.display = '';
    } else {
      /* Pendiente de confirmar → mostrar step A con productos sin código */
      subtit.textContent  = 'Confirma los productos que recibiste';
      const lista = _productos[pedidoId] || [];
      listaPrd.innerHTML  = lista.map(function (p) { return prodCard(p, false); }).join('') ||
        '<p style="color:var(--clr-text-muted);">Sin productos registrados.</p>';
      btnConf.disabled  = false;
      btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar y ver códigos';
      stepA.style.display = '';
      stepB.style.display = 'none';
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.cerrarModalPedido = function () {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  /* ─── Confirmación AJAX ─── */
  btnConf.addEventListener('click', function () {
    btnConf.disabled  = true;
    btnConf.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirmando…';

    fetch('confirmar_pedido.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'pedido_id=' + encodeURIComponent(activePedidoId),
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success && Array.isArray(data.productos)) {
        /* Cachear para reaperturas en la misma sesión de página */
        _codigosRuntime[activePedidoId] = data.productos;

        /* Transición a step B */
        listaCod.innerHTML  = data.productos.map(function (p) { return prodCard(p, true); }).join('') ||
          '<p style="color:var(--clr-text-muted);">Sin productos.</p>';
        attachCopy(listaCod);
        subtit.textContent  = 'Tus códigos de activación';
        stepA.style.display = 'none';
        stepB.style.display = '';

        /* Notificar a la página que lo incluyó */
        if (typeof window._mpOnConfirm === 'function') {
          window._mpOnConfirm(activePedidoId, data.productos);
        }
      } else {
        btnConf.disabled  = false;
        btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar y ver códigos';
        alert(data.error || 'Error al confirmar. Inténtalo de nuevo.');
      }
    })
    .catch(function () {
      btnConf.disabled  = false;
      btnConf.innerHTML = '<i class="fa-solid fa-unlock"></i> Confirmar y ver códigos';
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
