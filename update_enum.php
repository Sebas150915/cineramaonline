<?php
require_once 'includes/front_config.php';

try {
    // 1. Modify ENUM
    // Note: In MySQL, modifying an ENUM requires redefining all values
    $db->exec("ALTER TABLE tbl_ventas MODIFY COLUMN estado ENUM('PAGADO','ANULADO','PENDIENTE') DEFAULT 'PENDIENTE'");
    echo "ENUM actualizado correctamente.\n";
} catch (PDOException $e) {
    echo $e->getMessage();
}
