<?php
require_once '../../config/config.php';

try {
    $stmt = $db->prepare("SELECT id, usuario, rol, permiso_boleteria, permiso_dulceria FROM tbl_usuarios WHERE rol = 'cajero'");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
