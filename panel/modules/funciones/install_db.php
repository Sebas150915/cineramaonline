<?php
require_once '../../config/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS tbl_funciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_pelicula INT NOT NULL,
        id_sala INT NOT NULL,
        id_hora INT NOT NULL,
        fecha DATE NOT NULL,
        precio DECIMAL(10,2) DEFAULT 0.00,
        estado ENUM('0','1') DEFAULT '1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_pelicula) REFERENCES tbl_pelicula(id),
        FOREIGN KEY (id_sala) REFERENCES tbl_sala(id),
        FOREIGN KEY (id_hora) REFERENCES tbl_hora(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabla 'tbl_funciones' creada o verificada correctamente.";
} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage());
}
