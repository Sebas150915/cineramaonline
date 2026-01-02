<?php
require_once '../../config/config.php';

try {
    // 1. Crear tabla tbl_tarifas
    $sql1 = "CREATE TABLE IF NOT EXISTS tbl_tarifas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_local INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        dias_validos VARCHAR(50) DEFAULT NULL,
        estado ENUM('0','1') DEFAULT '1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_local) REFERENCES tbl_locales(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $db->exec($sql1);
    echo "Tabla 'tbl_tarifas' creada o verificada.<br>";

    // 2. Modificar tbl_funciones (Eliminar columna precio si existe)
    // Verificar si existe primero
    $check = $db->query("SHOW COLUMNS FROM tbl_funciones LIKE 'precio'");
    if ($check->rowCount() > 0) {
        $db->exec("ALTER TABLE tbl_funciones DROP COLUMN precio");
        echo "Columna 'precio' eliminada de 'tbl_funciones'.<br>";
    } else {
        echo "La columna 'precio' ya no existe en 'tbl_funciones'.<br>";
    }
} catch (PDOException $e) {
    die("Error en DB: " . $e->getMessage());
}
