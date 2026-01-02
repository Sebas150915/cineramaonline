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
    $stmt = $db->prepare("SELECT estado FROM tbl_pelicula WHERE id = ?");
    $stmt->execute([$id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        echo json_encode(['success' => false, 'message' => 'Película no encontrada']);
        exit;
    }

    // Cambiar estado
    $nuevo_estado = ($pelicula['estado'] == '1') ? '0' : '1';
    $stmt = $db->prepare("UPDATE tbl_pelicula SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    $mensaje = ($nuevo_estado == '1') ? 'Película activada correctamente' : 'Película desactivada correctamente';
    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
