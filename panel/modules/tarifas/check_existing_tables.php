<?php
require_once '../../config/config.php';
try {
    $tables = $db->query("SHOW TABLES LIKE 'tbl_tarifa%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas encontradas:\n";
    print_r($tables);

    if (in_array('tbl_tarifa', $tables)) {
        echo "\nEstructura de tbl_tarifa:\n";
        $stmt = $db->query("DESCRIBE tbl_tarifa");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
