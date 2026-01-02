<?php
require_once 'panel/config/config.php';

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Eliminar tabla si existe
    $sqlDrop = "DROP TABLE IF EXISTS `tbl_slider`";
    $db->exec($sqlDrop);
    echo "Tabla anterior eliminada (si existía).\n";

    // Crear nueva tabla con estructura correcta
    $sqlCreate = "CREATE TABLE `tbl_slider` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `titulo` varchar(100) DEFAULT NULL,
      `img` varchar(200) DEFAULT NULL,
      `link` varchar(255) DEFAULT NULL,
      `orden` int(11) DEFAULT '0',
      `estado` char(1) DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $db->exec($sqlCreate);
    echo "Nueva tabla tbl_slider creada correctamente con los campos: titulo, img, link, orden, estado.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
