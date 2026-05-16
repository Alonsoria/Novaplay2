-- NOVAPLAY — DB Patch OXXO Pay
-- Agrega columnas necesarias para el flujo de OXXO Pay (Conekta)
-- Ejecutar UNA SOLA VEZ en la BD novaplay2

ALTER TABLE pedidos
  ADD COLUMN metodo_pago      VARCHAR(20)  DEFAULT NULL AFTER estado,
  ADD COLUMN oxxo_order_id    VARCHAR(100) DEFAULT NULL AFTER metodo_pago,
  ADD COLUMN oxxo_referencia  VARCHAR(50)  DEFAULT NULL AFTER oxxo_order_id,
  ADD COLUMN oxxo_expira      DATETIME     DEFAULT NULL AFTER oxxo_referencia,
  ADD COLUMN oxxo_barcode_url TEXT         DEFAULT NULL AFTER oxxo_expira,
  ADD COLUMN oxxo_items_json  MEDIUMTEXT   DEFAULT NULL AFTER oxxo_barcode_url;
