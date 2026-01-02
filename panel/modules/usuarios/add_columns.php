<?php
require_once '../../config/config.php';

try {
    $db->exec("ALTER TABLE tbl_usuarios ADD COLUMN permiso_boleteria TINYINT(1) DEFAULT 0");
    $db->exec("ALTER TABLE tbl_usuarios ADD COLUMN permiso_dulceria TINYINT(1) DEFAULT 0");
    echo "Columns added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
