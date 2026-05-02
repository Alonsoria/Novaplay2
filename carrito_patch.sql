-- ============================================================
-- NOVAPLAY — PATCH DE BASE DE DATOS
-- Ejecutar una sola vez en phpMyAdmin o línea de comandos:
--   mysql -u root novaplay2 < carrito_patch.sql
--
-- Agrega:
--   1. Tabla `carrito` (faltaba en el schema original)
--   2. Columnas `puntos` y `ultima_recompensa` en `usuarios`
--   3. Datos de ejemplo en `combo_relacion`
-- ============================================================

USE `novaplay2`;

-- ── 1. TABLA CARRITO ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `carrito` (
  `id`          int(11)     NOT NULL AUTO_INCREMENT,
  `id_usuario`  int(11)     NOT NULL,
  `id_producto` int(11)     NOT NULL,
  `cantidad`    int(11)     NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_carrito_usuario`  (`id_usuario`),
  KEY `idx_carrito_producto` (`id_producto`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`id_usuario`)  REFERENCES `usuarios`  (`id_usuario`)  ON DELETE CASCADE,
  CONSTRAINT `carrito_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 2. COLUMNAS FALTANTES EN USUARIOS ────────────────────────
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `puntos`            int(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `ultima_recompensa` date    DEFAULT NULL;

-- ── 3. DATOS DE EJEMPLO PARA COMBO_RELACION ──────────────────
-- Combo 1: Xbox Game Pass Ultimate 3 meses (id 11)
-- Combo 2: Super Mario Odyssey (id 21) + Animal Crossing (id 20)
-- Combo 3: FIFA 24 (id 3) + PlayStation Plus Deluxe 3 meses (id 9)
INSERT IGNORE INTO `combo_relacion` (`id_combo`, `id_producto`) VALUES
(1, 11),
(2, 21),
(2, 20),
(3,  3),
(3,  9);
