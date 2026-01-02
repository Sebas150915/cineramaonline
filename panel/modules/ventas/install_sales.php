<?php
require_once '../../config/config.php';

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tabla Ventas (Cabecera)
    // removed 'fecha' column, we will use 'created_at' as the transaction date
    $sqlVentas = "CREATE TABLE IF NOT EXISTS tbl_ventas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(20) NOT NULL UNIQUE,
        id_funcion INT NOT NULL,
        cliente_nombre VARCHAR(150),
        cliente_doc VARCHAR(20),
        total DECIMAL(10,2) NOT NULL,
        tipo_comprobante ENUM('BOLETA','FACTURA') DEFAULT 'BOLETA',
        medio_pago VARCHAR(50) DEFAULT 'EFECTIVO',
        estado ENUM('PAGADO','ANULADO') DEFAULT 'PAGADO',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_funcion) REFERENCES tbl_funciones(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlVentas);
    echo "Tabla 'tbl_ventas' verificada/creada.<br>";

    // 2. Tabla Boletos (Detalle)
    $sqlBoletos = "CREATE TABLE IF NOT EXISTS tbl_boletos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_venta INT NOT NULL,
        id_asiento INT, 
        fila VARCHAR(5),
        columna VARCHAR(5),
        letra VARCHAR(5),
        numero VARCHAR(5),
        id_tarifa INT,
        precio DECIMAL(10,2) NOT NULL,
        estado ENUM('ACTIVO','ANULADO') DEFAULT 'ACTIVO',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_venta) REFERENCES tbl_ventas(id),
        FOREIGN KEY (id_tarifa) REFERENCES tbl_tarifa(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sqlBoletos);
    echo "Tabla 'tbl_boletos' verificada/creada.<br>";
} catch (PDOException $e) {
    echo "Error en DB: " . $e->getMessage();
    exit(1);
}
