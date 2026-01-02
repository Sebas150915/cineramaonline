<?php
require_once 'includes/front_config.php';
try {
    $stmt = $db->query("DESCRIBE tbl_ventas");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e;
}
