<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'Sin permisos']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Obtener estado actual
    $stmt = $db->prepare("SELECT estado FROM tbl_categoria WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();

    if (!$cat) {
        echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        exit;
    }

    // Cambiar estado
    $nuevo_estado = ($cat['estado'] == '1') ? '0' : '1';
    $stmt = $db->prepare("UPDATE tbl_categoria SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    $mensaje = ($nuevo_estado == '1') ? 'Categoría activada' : 'Categoría desactivada';
    echo json_encode(['success' => true, 'message' => $mensaje]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
