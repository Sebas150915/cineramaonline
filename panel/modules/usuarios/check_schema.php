<?php
require_once '../../config/config.php';

try {
    $stmt = $db->query("DESCRIBE tbl_usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in tbl_usuarios:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
