-- NOVAPLAY — Migración: restablecimiento de contraseña
-- Ejecutar UNA sola vez sobre la base de datos novaplay2

ALTER TABLE usuarios
  ADD COLUMN reset_token         VARCHAR(64) NULL DEFAULT NULL AFTER verification_expires,
  ADD COLUMN reset_token_expires DATETIME   NULL DEFAULT NULL AFTER reset_token,
  ADD INDEX  idx_reset_token (reset_token);
