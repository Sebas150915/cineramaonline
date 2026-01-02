<?php
require_once 'panel/config/config.php';
try {
    $stmt = $db->query("DESCRIBE tbl_ventas");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
