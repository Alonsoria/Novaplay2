-- NOVAPLAY — SCHEMA V3
-- Agrega columna oxxo_redirect_url para almacenar la URL de pago de Conekta Efectivo.
-- Ejecutar una sola vez en phpMyAdmin o CLI de MySQL.

ALTER TABLE pedidos
  ADD COLUMN oxxo_redirect_url TEXT DEFAULT NULL AFTER oxxo_barcode_url;
