<?php
require_once '../../config/config.php';

try {
    $stmt = $db->prepare("SELECT usuario, rol, permiso_dulceria, permiso_boleteria FROM tbl_usuarios WHERE usuario = ?");
    $stmt->execute(['cajero1']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($user);
} catch (PDOException $e) {
    echo "Error";
}
