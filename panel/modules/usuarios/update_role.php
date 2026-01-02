<?php
require_once '../../config/config.php';

try {
    $stmt = $db->prepare("UPDATE tbl_usuarios SET rol = 'cajero' WHERE usuario = ?");
    $stmt->execute(['cajero1']);
    echo "User cajero1 updated to role: cajero";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
