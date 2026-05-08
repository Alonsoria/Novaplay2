-- =====================================================================
-- NovaPlay DB Patch v5
-- Sistema de devoluciones + suscripciones como productos
-- Compatible con MySQL 5.7 / 8.0 — ejecutar desde MySQL Workbench
-- Idempotente: seguro de correr múltiples veces.
-- =====================================================================

SET @OLD_UNIQUE_CHECKS      = @@UNIQUE_CHECKS,      UNIQUE_CHECKS      = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE            = @@SQL_MODE,            SQL_MODE           = 'TRADITIONAL,ALLOW_INVALID_DATES';

-- ---------------------------------------------------------------------
-- 1. Columna es_suscripcion en productos (si no existe)
-- ---------------------------------------------------------------------

DROP PROCEDURE IF EXISTS _nova_v5_add_col;

DELIMITER $$
CREATE PROCEDURE _nova_v5_add_col()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM   INFORMATION_SCHEMA.COLUMNS
    WHERE  TABLE_SCHEMA = DATABASE()
      AND  TABLE_NAME   = 'productos'
      AND  COLUMN_NAME  = 'es_suscripcion'
  ) THEN
    ALTER TABLE productos
      ADD COLUMN `es_suscripcion` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = plan de suscripción, 0 = producto normal';
  END IF;
END$$
DELIMITER ;

CALL _nova_v5_add_col();
DROP PROCEDURE IF EXISTS _nova_v5_add_col;

-- ---------------------------------------------------------------------
-- 2. Insertar planes de suscripción como productos (evita duplicados)
-- ---------------------------------------------------------------------

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'Xbox Game Pass Essential',
       'Más de 50 juegos en la consola Xbox, PC y dispositivos compatibles. Multijugador en línea.',
       'suscripcion', 129.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'Xbox Game Pass Essential' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'Xbox Game Pass Premium',
       'Más de 200 juegos en la consola Xbox, PC y dispositivos. Multijugador en línea.',
       'suscripcion', 179.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'Xbox Game Pass Premium' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'Xbox Game Pass Ultimate',
       'Más de 400 juegos desde el día de lanzamiento. EA Play, Ubisoft+ Classics, cloud gaming.',
       'suscripcion', 289.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'Xbox Game Pass Ultimate' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'PS Plus Essential',
       'Juegos mensuales, multijugador online y descuentos exclusivos en PlayStation Store.',
       'suscripcion', 119.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'PS Plus Essential' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'PS Plus Extra',
       'Todo lo de Essential + catálogo de cientos de juegos PS4/PS5 + Ubisoft+.',
       'suscripcion', 179.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'PS Plus Extra' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'PS Plus Deluxe',
       'Todo lo de Extra + catálogo clásico PS1/PS2/PSP + streaming + pruebas de tiempo limitado.',
       'suscripcion', 209.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'PS Plus Deluxe' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'Nintendo Switch Online',
       'Multijugador online, NES + SNES clásicos, almacenamiento en la nube y ofertas exclusivas.',
       'suscripcion', 309.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'Nintendo Switch Online' AND `es_suscripcion` = 1
);

INSERT INTO `productos` (`nombre`, `descripcion`, `categoria`, `precio`, `es_suscripcion`)
SELECT 'Nintendo Switch Online + Paquete Expansión',
       'Todo el plan básico + juegos clásicos (N64, Genesis, GBA, GBC) + DLC gratuito.',
       'suscripcion', 859.00, 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `productos` WHERE `nombre` = 'Nintendo Switch Online + Paquete Expansión' AND `es_suscripcion` = 1
);

-- ---------------------------------------------------------------------
-- 3. Relacionar suscripciones con sus plataformas en producto_plataforma
--    plataformas: Xbox=1, PlayStation=2, Nintendo=4
-- ---------------------------------------------------------------------

INSERT INTO `producto_plataforma` (`id_producto`, `id_plataforma`)
SELECT p.`id_producto`, 1
FROM   `productos` p
WHERE  p.`nombre` IN ('Xbox Game Pass Essential', 'Xbox Game Pass Premium', 'Xbox Game Pass Ultimate')
  AND  p.`es_suscripcion` = 1
  AND  NOT EXISTS (
    SELECT 1 FROM `producto_plataforma` pp
    WHERE pp.`id_producto`  = p.`id_producto`
      AND pp.`id_plataforma` = 1
  );

INSERT INTO `producto_plataforma` (`id_producto`, `id_plataforma`)
SELECT p.`id_producto`, 2
FROM   `productos` p
WHERE  p.`nombre` IN ('PS Plus Essential', 'PS Plus Extra', 'PS Plus Deluxe')
  AND  p.`es_suscripcion` = 1
  AND  NOT EXISTS (
    SELECT 1 FROM `producto_plataforma` pp
    WHERE pp.`id_producto`  = p.`id_producto`
      AND pp.`id_plataforma` = 2
  );

INSERT INTO `producto_plataforma` (`id_producto`, `id_plataforma`)
SELECT p.`id_producto`, 4
FROM   `productos` p
WHERE  p.`nombre` IN ('Nintendo Switch Online', 'Nintendo Switch Online + Paquete Expansión')
  AND  p.`es_suscripcion` = 1
  AND  NOT EXISTS (
    SELECT 1 FROM `producto_plataforma` pp
    WHERE pp.`id_producto`  = p.`id_producto`
      AND pp.`id_plataforma` = 4
  );

-- ---------------------------------------------------------------------
-- 4. Tabla de solicitudes de devolución
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `solicitudes_devolucion` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `id_pedido`  INT          NOT NULL,
  `id_usuario` INT          NOT NULL,
  `productos`  TEXT         NOT NULL COMMENT 'JSON array con los nombres de productos a devolver',
  `estado`     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `fecha`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  INDEX `idx_pedido`  (`id_pedido`),
  INDEX `idx_usuario` (`id_usuario`),
  INDEX `idx_estado`  (`estado`)
) ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------

SET SQL_MODE             = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS   = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS        = @OLD_UNIQUE_CHECKS;
