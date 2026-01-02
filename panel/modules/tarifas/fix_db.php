<?php
require_once '../../config/config.php';

try {
    // 1. Drop the duplicate table I created
    $db->exec("DROP TABLE IF EXISTS tbl_tarifas");
    echo "Tabla 'tbl_tarifas' eliminada.<br>";

    // 2. Fix the existing table 'tbl_tarifa'
    // 'local' column is char(1) which is too small for IDs > 9. Change to INT.
    // Also ensuring 'precio' is DECIMAL(10,2) just in case.
    $db->exec("ALTER TABLE tbl_tarifa MODIFY local INT NOT NULL");
    echo "Columna 'local' en 'tbl_tarifa' modificada a INT.<br>";

    // Verify
    $stmt = $db->query("DESCRIBE tbl_tarifa");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
