<?php
require_once 'panel/config/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS tbl_usuarios (
        id INT(11) NOT NULL AUTO_INCREMENT,
        usuario VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        rol ENUM('admin', 'cajero') DEFAULT 'cajero',
        estado CHAR(1) DEFAULT '1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;";

    $db->exec($sql);
    echo "Table tbl_usuarios created successfully.\n";

    // Create default cashier
    $pass = password_hash('123456', PASSWORD_DEFAULT);
    $check = $db->query("SELECT id FROM tbl_usuarios WHERE usuario = 'cajero1'");
    if ($check->rowCount() == 0) {
        $stmt = $db->prepare("INSERT INTO tbl_usuarios (usuario, password, nombre, rol, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['cajero1', $pass, 'Cajero Principal', 'cajero', '1']);
        echo "Default user 'cajero1' created.\n";
    } else {
        echo "User 'cajero1' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
