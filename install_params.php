<?php
require_once 'includes/front_config.php';

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS tbl_parametros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(50) NOT NULL UNIQUE,
        valor VARCHAR(255) NOT NULL,
        descripcion TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'tbl_parametros' creada.<br>";

    // Insert Defaults
    $params = [
        ['clave' => 'compra_max_butacas', 'valor' => '5', 'desc' => 'Máximo de butacas por compra'],
        ['clave' => 'compra_tiempo_limite', 'valor' => '5', 'desc' => 'Tiempo límite en minutos para completar la compra']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO tbl_parametros (clave, valor, descripcion) VALUES (?, ?, ?)");

    foreach ($params as $p) {
        $stmt->execute([$p['clave'], $p['valor'], $p['desc']]);
    }
    echo "Parámetros insertados/verificados.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
