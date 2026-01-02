<?php
require_once '../../config/config.php';
$tables = ['tbl_usuarios', 'tbl_locales', 'tbl_empresa'];
foreach ($tables as $t) {
    echo "DESCRIBE $t:\n";
    try {
        $stmt = $db->query("DESCRIBE $t");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            echo $col['Field'] . " " . $col['Type'] . "\n";
        }
    } catch (PDOException $e) {
        echo "Error describe $t: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
