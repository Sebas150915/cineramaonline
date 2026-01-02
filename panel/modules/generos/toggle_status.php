<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Obtener estado actual
    $stmt = $db->prepare("SELECT estado FROM tbl_genero WHERE id = ?");
    $stmt->execute([$id]);
    $genero = $stmt->fetch();

    if (!$genero) {
        echo json_encode(['success' => false, 'message' => 'Género no encontrado']);
        exit;
    }

    // Cambiar estado
    $nuevo_estado = ($genero['estado'] == '1') ? '0' : '1';
    $stmt = $db->prepare("UPDATE tbl_genero SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    $mensaje = ($nuevo_estado == '1') ? 'Género activado correctamente' : 'Género desactivado correctamente';
    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
