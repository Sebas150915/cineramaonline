<?php
require_once '../../config/config.php';
try {
    $tables = $db->query("SHOW TABLES LIKE 'tbl_vent%'")->fetchAll(PDO::FETCH_COLUMN);
    $tables2 = $db->query("SHOW TABLES LIKE 'tbl_bol%'")->fetchAll(PDO::FETCH_COLUMN);
    $all = array_merge($tables, $tables2);

    echo "Tablas encontradas:\n";
    print_r($all);

    foreach ($all as $t) {
        echo "\nEstructura de $t:\n";
        $stmt = $db->query("DESCRIBE $t");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . " - " . $col['Type'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
