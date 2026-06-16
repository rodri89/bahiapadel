-- Salidas de dinero en caja (egresos por fecha: proveedores, retiros, etc.)
-- Ejecutar directamente en MySQL / MariaDB (compatible con MySQL 5.7 / phpMyAdmin)
-- Es idempotente: si la tabla ya existe no falla.

CREATE TABLE IF NOT EXISTS stock_caja_salidas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fecha DATE NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    metodo VARCHAR(20) NOT NULL COMMENT 'efectivo | transferencia',
    descripcion TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY stock_caja_salidas_fecha_index (fecha),
    KEY stock_caja_salidas_metodo_index (metodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
