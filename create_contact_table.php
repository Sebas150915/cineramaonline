<?php
require_once 'includes/front_config.php';

try {
    // MySQL 5.5 compatibility: using TIMESTAMP instead of DATETIME for CURRENT_TIMESTAMP default
    $sql = "CREATE TABLE IF NOT EXISTS tbl_contactos (
        id INT(11) NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(100) NOT NULL,
        apellidos VARCHAR(100) NOT NULL,
        correo VARCHAR(100) NOT NULL,
        asunto VARCHAR(150),
        cine VARCHAR(100),
        mensaje TEXT,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado CHAR(1) DEFAULT '0', -- 0: No leido, 1: Leido
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";

    $db->exec($sql);
    echo "Table tbl_contactos created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
