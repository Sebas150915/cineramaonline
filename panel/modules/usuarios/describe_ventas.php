<?php
require_once '../../config/config.php';
try {
    $stmt = $db->query("DESCRIBE tbl_ventas");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo $col['Field'] . " " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
