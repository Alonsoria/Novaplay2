-- =====================================================================
-- NovaPlay DB Patch v6
-- Seguimiento granular de ítems devueltos + monto de reembolso
-- Compatible con MySQL 5.7 / 8.0 — ejecutar desde MySQL Workbench
-- Idempotente: seguro de correr múltiples veces.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Marcar cada código individualmente como devuelto
-- ---------------------------------------------------------------------

DROP PROCEDURE IF EXISTS _nova_v6_col_devuelto;
DELIMITER $$
CREATE PROCEDURE _nova_v6_col_devuelto()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM   INFORMATION_SCHEMA.COLUMNS
    WHERE  TABLE_SCHEMA = DATABASE()
      AND  TABLE_NAME   = 'codigos_activacion'
      AND  COLUMN_NAME  = 'devuelto'
  ) THEN
    ALTER TABLE codigos_activacion
      ADD COLUMN `devuelto` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = este código fue devuelto y reembolsado';
  END IF;
END$$
DELIMITER ;
CALL _nova_v6_col_devuelto();
DROP PROCEDURE IF EXISTS _nova_v6_col_devuelto;

-- ---------------------------------------------------------------------
-- 2. Monto total reembolsado por pedido
-- ---------------------------------------------------------------------

DROP PROCEDURE IF EXISTS _nova_v6_col_monto;
DELIMITER $$
CREATE PROCEDURE _nova_v6_col_monto()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM   INFORMATION_SCHEMA.COLUMNS
    WHERE  TABLE_SCHEMA = DATABASE()
      AND  TABLE_NAME   = 'pedidos'
      AND  COLUMN_NAME  = 'monto_devuelto'
  ) THEN
    ALTER TABLE pedidos
      ADD COLUMN `monto_devuelto` DECIMAL(10,2) NOT NULL DEFAULT 0.00
        COMMENT 'Suma de precios de ítems reembolsados';
  END IF;
END$$
DELIMITER ;
CALL _nova_v6_col_monto();
DROP PROCEDURE IF EXISTS _nova_v6_col_monto;

-- ---------------------------------------------------------------------
