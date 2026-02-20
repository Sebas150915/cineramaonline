<?php
require_once '../config/config.php';

echo "<h2>Actualizando Base de Datos...</h2>";

try {
    // 1. Crear tabla tbl_categoria
    $sql = "CREATE TABLE IF NOT EXISTS `tbl_categoria` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nombre` varchar(50) NOT NULL,
      `estado` char(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "Tabla 'tbl_categoria' verificada/creada.<br>";

    // 2. Agregar columnas a tbl_productos
    // Verificar si existen antes de agregar para evitar error
    $columns = $db->query("SHOW COLUMNS FROM tbl_productos")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('idcategoria', $columns)) {
        $db->exec("ALTER TABLE `tbl_productos` ADD `idcategoria` INT(11) NULL AFTER `imagen`;");
        echo "Columna 'idcategoria' agregada a 'tbl_productos'.<br>";
    } else {
        echo "Columna 'idcategoria' ya existe en 'tbl_productos'.<br>";
    }

    if (!in_array('afectacion', $columns)) {
        $db->exec("ALTER TABLE `tbl_productos` ADD `afectacion` CHAR(2) NULL COMMENT '10 AFECTO, 20 EXONERADO , 30 INAFECTO' AFTER `idcategoria`;");
        echo "Columna 'afectacion' agregada a 'tbl_productos'.<br>";
    } else {
        echo "Columna 'afectacion' ya existe en 'tbl_productos'.<br>";
    }

    echo "<h3>Actualización completada con éxito.</h3>";
} catch (PDOException $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
}
