-- ============================================================
-- NOVAPLAY — DB PATCH v2
-- Ejecutar en phpMyAdmin → base de datos novaplay2 → SQL
-- ============================================================

USE `novaplay2`;

-- ── 1. COLUMNAS NUEVAS EN USUARIOS ───────────────────────────
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `puntos_reset_mes` VARCHAR(7)   DEFAULT NULL   COMMENT 'YYYY-MM del último reset mensual de puntos',
  ADD COLUMN IF NOT EXISTS `razon_social`     VARCHAR(150) DEFAULT NULL   COMMENT 'Razón social para facturación';

-- ── 2. TABLA TARJETAS_GUARDADAS (tokenización simulada) ──────
CREATE TABLE IF NOT EXISTS `tarjetas_guardadas` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11)      NOT NULL,
  `token`      char(64)     NOT NULL              COMMENT 'Token único de la tarjeta',
  `ultimos4`   char(4)      NOT NULL              COMMENT 'Últimos 4 dígitos',
  `marca`      varchar(20)  DEFAULT 'Desconocida' COMMENT 'Visa / Mastercard / etc.',
  `expiry`     char(5)      NOT NULL              COMMENT 'MM/AA',
  `alias`      varchar(60)  DEFAULT NULL,
  `creado`     timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `tarjetas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 3. TABLA NOTIFICACIONES ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11)      NOT NULL,
  `mensaje`    varchar(255) NOT NULL,
  `leida`      tinyint(1)   NOT NULL DEFAULT 0,
  `creado`     timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `notif_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
