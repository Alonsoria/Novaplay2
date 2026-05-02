-- ============================================================
-- NOVAPLAY — DB PATCH v3
-- Ejecutar en phpMyAdmin → base de datos novaplay2 → SQL
-- ============================================================

USE `novaplay2`;

-- ── 1. Racha de recompensa diaria ────────────────────────────
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `racha_recompensa` INT NOT NULL DEFAULT 0
    COMMENT 'Número de día consecutivo actual (1-7), se resetea al completar el ciclo';

-- ── 2. Tabla de códigos de activación por compra ─────────────
CREATE TABLE IF NOT EXISTS `codigos_activacion` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `id_pedido`       int(11)      NOT NULL,
  `id_usuario`      int(11)      NOT NULL,
  `id_producto`     int(11)      DEFAULT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `imagen_producto` varchar(255) DEFAULT NULL,
  `codigo`          char(16)     NOT NULL COMMENT '16 chars alfanumérico único',
  `creado`          timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `codigos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
