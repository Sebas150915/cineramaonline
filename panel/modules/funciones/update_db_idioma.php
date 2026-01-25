<?php
require_once '../../config/config.php';

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM tbl_funciones LIKE 'idioma'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE tbl_funciones ADD COLUMN idioma VARCHAR(20) DEFAULT 'Doblada' AFTER id_hora";
        $db->exec($sql);
        echo "Columna 'idioma' agregada correctamente.";
    } else {
        echo "La columna 'idioma' ya existe.";
    }
} catch (PDOException $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage());
}
