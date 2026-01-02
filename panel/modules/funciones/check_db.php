<?php
require_once '../../config/config.php';
$stmt = $db->query("DESCRIBE tbl_funciones");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}
