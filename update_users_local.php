<?php
require_once 'panel/config/config.php';

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM tbl_usuarios LIKE 'id_local'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE tbl_usuarios ADD COLUMN id_local INT(11) AFTER rol");
        echo "Column id_local added.\n";
    }

    // Assign generic local to cajero1 (Assuming ID 1 exists, usually Minka or Pacifico)
    // Let's check locales first
    $stmtL = $db->query("SELECT id, nombre FROM tbl_locales LIMIT 1");
    $local = $stmtL->fetch();

    if ($local) {
        $stmtU = $db->prepare("UPDATE tbl_usuarios SET id_local = ? WHERE usuario = 'cajero1'");
        $stmtU->execute([$local['id']]);
        echo "Assigned 'cajero1' to Local: " . $local['nombre'] . " (ID: " . $local['id'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
