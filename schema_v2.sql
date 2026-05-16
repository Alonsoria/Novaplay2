-- ============================================================
--  Novaplay — Migración v2
--  Confirmación granular por ítem de pedido.
--  Ejecutar UNA sola vez en cada entorno (local + producción).
-- ============================================================

-- 1. Columna de confirmación individual en codigos_activacion
ALTER TABLE codigos_activacion
  ADD COLUMN confirmado TINYINT(1) NOT NULL DEFAULT 0
  AFTER devuelto;

-- 2. (Opcional) Índice para acelerar consultas de ítems pendientes
ALTER TABLE codigos_activacion
  ADD INDEX idx_pedido_estado (id_pedido, devuelto, confirmado);
