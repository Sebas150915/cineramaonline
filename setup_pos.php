<?php
require_once 'panel/config/config.php';

try {
    $db->beginTransaction();

    // 1. Table: tbl_formas_pago
    $db->exec("CREATE TABLE IF NOT EXISTS tbl_formas_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL,
        estado CHAR(1) DEFAULT '1'
    )");

    // Seed Payments if empty
    $stmt = $db->query("SELECT COUNT(*) FROM tbl_formas_pago");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO tbl_formas_pago (nombre) VALUES ('Efectivo'), ('Visa'), ('Yape'), ('Plin')");
        echo "Seeded tbl_formas_pago.\n";
    }

    // 2. Table: tbl_series (Facturación)
    $db->exec("CREATE TABLE IF NOT EXISTS tbl_series (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo CHAR(1) NOT NULL COMMENT 'B=Boleta, F=Factura',
        serie VARCHAR(10) NOT NULL COMMENT 'B001, F001',
        correlativo INT DEFAULT 0,
        id_usuario INT DEFAULT NULL,
        estado CHAR(1) DEFAULT '1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'tbl_series' checked/created.\n";

    // 3. Table: tbl_ventas_dulceria
    $db->exec("CREATE TABLE IF NOT EXISTS tbl_ventas_dulceria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
        id_usuario INT NOT NULL,
        cliente_nombre VARCHAR(100) DEFAULT 'Público General',
        total DECIMAL(10, 2) NOT NULL,
        serie VARCHAR(10) NOT NULL,
        correlativo INT NOT NULL,
        estado ENUM('PAGADO', 'ANULADO') DEFAULT 'PAGADO',
        observaciones TEXT
    )");
    echo "Table 'tbl_ventas_dulceria' checked/created.\n";

    // 4. Table: tbl_ventas_dulceria_detalle
    $db->exec("CREATE TABLE IF NOT EXISTS tbl_ventas_dulceria_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_venta INT NOT NULL,
        id_producto INT NOT NULL,
        cantidad DECIMAL(10, 2) NOT NULL,
        precio_unitario DECIMAL(10, 2) NOT NULL,
        importe DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (id_venta) REFERENCES tbl_ventas_dulceria(id) ON DELETE CASCADE
    )");
    echo "Table 'tbl_ventas_dulceria_detalle' checked/created.\n";

    // 5. Table: tbl_ventas_pagos (For Split Payments)
    $db->exec("CREATE TABLE IF NOT EXISTS tbl_ventas_pagos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_venta INT NOT NULL,
        id_forma_pago INT NOT NULL,
        monto DECIMAL(10, 2) NOT NULL,
        referencia VARCHAR(100) DEFAULT NULL,
        FOREIGN KEY (id_venta) REFERENCES tbl_ventas_dulceria(id) ON DELETE CASCADE,
        FOREIGN KEY (id_forma_pago) REFERENCES tbl_formas_pago(id)
    )");
    echo "Table 'tbl_ventas_pagos' checked/created.\n";

    // 6. Modify tbl_usuarios
    // Check if column exists first to avoid error
    $columns = $db->query("SHOW COLUMNS FROM tbl_usuarios LIKE 'permiso_dulceria'")->fetchAll();
    if (empty($columns)) {
        $db->exec("ALTER TABLE tbl_usuarios ADD COLUMN permiso_dulceria TINYINT(1) DEFAULT 0 AFTER rol");
        $db->exec("ALTER TABLE tbl_usuarios ADD COLUMN permiso_boleteria TINYINT(1) DEFAULT 0 AFTER permiso_dulceria");
        echo "Columns added to tbl_usuarios.\n";
    }

    $db->commit();
    echo "Migration completed successfully.\n";
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
