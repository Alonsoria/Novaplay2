-- ============================================================
-- NOVAPLAY — DB PATCH v4
-- Verificación de cuenta por correo + rate limiting de registro
-- Ejecutar en phpMyAdmin → base de datos novaplay2 → SQL
-- ============================================================

USE `novaplay2`;

-- ── 1. Columnas de verificación en usuarios ──────────────────
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `verificado` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0 = pendiente verificación, 1 = cuenta verificada',
  ADD COLUMN IF NOT EXISTS `verification_code` VARCHAR(8) DEFAULT NULL
    COMMENT 'Código OTP de 6 chars (se borra tras verificar)',
  ADD COLUMN IF NOT EXISTS `verification_expires` DATETIME DEFAULT NULL
    COMMENT 'Expiración del código (15 minutos desde generación)';

-- ── 2. Marcar como verificados a todos los usuarios existentes ─
--     Para no bloquear cuentas creadas antes de este parche
UPDATE `usuarios` SET `verificado` = 1 WHERE `verificado` = 0;

-- ── 3. Tabla para rate limiting de registros por IP ──────────
CREATE TABLE IF NOT EXISTS `registro_intentos` (
  `id`     int(11)     NOT NULL AUTO_INCREMENT,
  `ip`     varchar(45) NOT NULL               COMMENT 'IPv4 o IPv6',
  `creado` timestamp   NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_creado` (`ip`, `creado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
