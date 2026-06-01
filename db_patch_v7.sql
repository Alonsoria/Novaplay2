-- ============================================================
-- NOVAPLAY — DB PATCH v7
-- Agrega columna puntos_reset_fecha para control del reset
-- semestral de puntos (1 de enero y 1 de julio).
-- Agrega estado 'cancelado' a pedidos (vencimiento OXXO).
-- ============================================================

-- 1. Columna para rastrear la fecha del último reset de puntos
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS puntos_reset_fecha DATE NULL DEFAULT NULL
    COMMENT 'Fecha del último reset semestral de puntos (1-ene o 1-jul)';

-- 2. Ampliar el ENUM de pedidos para incluir 'cancelado'
--    (MariaDB/MySQL permiten ALTER COLUMN MODIFY para cambiar el ENUM)
ALTER TABLE pedidos
    MODIFY COLUMN estado ENUM(
        'pagado',
        'pendiente_oxxo',
        'reembolsado',
        'cancelado'
    ) NOT NULL DEFAULT 'pagado';
