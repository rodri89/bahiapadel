-- Cambios para el módulo de Caja (dividir productos + ticket de continuación)
-- Ejecutar directamente en MySQL / MariaDB
-- Es idempotente: si la columna ya existe no falla.

-- 1) Permite marcar líneas de venta que son resultado de una división entre jugadores
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_es_division()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_detalles_venta'
          AND COLUMN_NAME = 'es_division'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_detalles_venta
            ADD COLUMN es_division TINYINT(1) NOT NULL DEFAULT 0
            AFTER stock_venta_participante_id;
    END IF;
END //
DELIMITER ;
CALL add_es_division();
DROP PROCEDURE IF EXISTS add_es_division;

-- 2) Permite vincular un ticket nuevo (hijo) a un ticket ya cerrado (padre)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_stock_venta_id_padre()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'stock_ventas'
          AND COLUMN_NAME = 'stock_venta_id_padre'
          AND TABLE_SCHEMA = DATABASE()
    ) THEN
        ALTER TABLE stock_ventas
            ADD COLUMN stock_venta_id_padre BIGINT UNSIGNED NULL
            AFTER id;
    END IF;
END //
DELIMITER ;
CALL add_stock_venta_id_padre();
DROP PROCEDURE IF EXISTS add_stock_venta_id_padre;

-- Opcional: Foreign Key (si tu BD lo soporta y querés mantener integridad referencial)
-- Ejecutar solo si no existe ya la FK:
-- ALTER TABLE stock_ventas
--     ADD CONSTRAINT fk_stock_ventas_padre
--     FOREIGN KEY (stock_venta_id_padre) REFERENCES stock_ventas(id)
--     ON DELETE SET NULL;
