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
    $stmt = $db->prepare("SELECT estado FROM tbl_sala WHERE id = ?");
    $stmt->execute([$id]);
    $sala = $stmt->fetch();

    if (!$sala) {
        echo json_encode(['success' => false, 'message' => 'Sala no encontrada']);
        exit;
    }

    // Cambiar estado
    $nuevo_estado = ($sala['estado'] == '1') ? '0' : '1';
    $stmt = $db->prepare("UPDATE tbl_sala SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    $mensaje = ($nuevo_estado == '1') ? 'Sala activada correctamente' : 'Sala desactivada correctamente';
    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
