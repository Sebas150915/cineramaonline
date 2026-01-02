<?php
require_once 'config/config.php';

try {
    echo "Updating database schema for roles...\n";

    // 1. Modify 'rol' enum to include new roles
    // We cannot easily 'ALTER COLUMN' for ENUM in one go without redefining it.
    // We will update it to include new roles.
    $sql = "ALTER TABLE tbl_usuarios MODIFY COLUMN rol ENUM('superadmin', 'supervisor', 'ventas', 'admin', 'cajero') DEFAULT 'ventas'";
    $db->exec($sql);
    echo "Column 'rol' updated.\n";

    // 2. Add 'id_local' column if it doesn't exist
    // Check if column exists first
    $stmt = $db->query("SHOW COLUMNS FROM tbl_usuarios LIKE 'id_local'");
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE tbl_usuarios ADD COLUMN id_local INT(11) NULL DEFAULT NULL AFTER rol";
        $db->exec($sql);
        echo "Column 'id_local' added.\n";
    } else {
        echo "Column 'id_local' already exists.\n";
    }

    // 3. Create Default Users
    // Get a valid local ID
    $stmt = $db->query("SELECT id FROM tbl_locales LIMIT 1");
    $local_id = $stmt->fetchColumn();

    if (!$local_id) {
        // Create a dummy local if none exists (unlikely given the app)
        $db->exec("INSERT INTO tbl_locales (nombre, orden, empresa, venta, estado) VALUES ('Cine Test', 1, 1, 'SI', '1')");
        $local_id = $db->lastInsertId();
    }

    $users = [
        [
            'user' => 'superadmin',
            'pass' => 'admin123',
            'name' => 'Super Administrador',
            'rol' => 'superadmin',
            'local' => null
        ],
        [
            'user' => 'supervisor1',
            'pass' => 'cine123',
            'name' => 'Supervisor Cine 1',
            'rol' => 'supervisor',
            'local' => $local_id
        ],
        [
            'user' => 'ventas1',
            'pass' => 'ventas123',
            'name' => 'Ventas Cine 1',
            'rol' => 'ventas',
            'local' => $local_id
        ]
    ];

    foreach ($users as $u) {
        $check = $db->prepare("SELECT id FROM tbl_usuarios WHERE usuario = ?");
        $check->execute([$u['user']]);

        if ($check->rowCount() == 0) {
            $passHash = password_hash($u['pass'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO tbl_usuarios (usuario, password, nombre, rol, id_local, estado) VALUES (?, ?, ?, ?, ?, '1')");
            $stmt->execute([$u['user'], $passHash, $u['name'], $u['rol'], $u['local']]);
            echo "User '{$u['user']}' created.\n";
        } else {
            // Update role/local if needed to ensure testing works
            $stmt = $db->prepare("UPDATE tbl_usuarios SET rol = ?, id_local = ? WHERE usuario = ?");
            $stmt->execute([$u['rol'], $u['local'], $u['user']]);
            echo "User '{$u['user']}' updated.\n";
        }
    }

    echo "Setup completed successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
