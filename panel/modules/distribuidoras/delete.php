<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Verificar si la distribuidora está en uso
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_pelicula WHERE distribuidora = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        showAlert('warning', 'Advertencia', 'No se puede eliminar la distribuidora porque está siendo usada por ' . $result['total'] . ' película(s)');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al verificar la distribuidora: ' . $e->getMessage());
    redirect('index.php');
}

// Eliminar distribuidora
try {
    $stmt = $db->prepare("DELETE FROM tbl_distribuidora WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Distribuidora eliminada correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar la distribuidora: ' . $e->getMessage());
}

redirect('index.php');
