<?php
require_once 'panel/config/config.php';

try {
    // Table: tbl_productos
    $sqlProductos = "CREATE TABLE IF NOT EXISTS tbl_productos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        tipo ENUM('producto', 'insumo', 'combo') NOT NULL DEFAULT 'producto',
        codigo_barras VARCHAR(50) DEFAULT NULL,
        precio_venta DECIMAL(10, 2) DEFAULT 0.00,
        precio_base DECIMAL(10, 2) DEFAULT 0.00,
        igv_tipo ENUM('gravado', 'exonerado', 'inafecto') DEFAULT 'gravado',
        igv_porcentaje DECIMAL(5, 2) DEFAULT 18.00,
        es_vendible TINYINT(1) DEFAULT 1,
        stock DECIMAL(10, 2) DEFAULT 0.00,
        unidad_medida VARCHAR(20) DEFAULT 'NIU',
        estado CHAR(1) DEFAULT '1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    )";
    $db->exec($sqlProductos);
    echo "Table 'tbl_productos' created/checked.\n";

    // Table: tbl_recetas
    $sqlRecetas = "CREATE TABLE IF NOT EXISTS tbl_recetas (
        id INT(11) NOT NULL AUTO_INCREMENT,
        id_producto_padre INT(11) NOT NULL,
        id_producto_hijo INT(11) NOT NULL,
        cantidad DECIMAL(10, 2) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (id_producto_padre) REFERENCES tbl_productos(id) ON DELETE CASCADE,
        FOREIGN KEY (id_producto_hijo) REFERENCES tbl_productos(id) ON DELETE CASCADE
    )";
    $db->exec($sqlRecetas);
    echo "Table 'tbl_recetas' created/checked.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
