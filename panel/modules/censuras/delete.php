<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Verificar si la censura está en uso
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_pelicula WHERE censura = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        showAlert('warning', 'Advertencia', 'No se puede eliminar la censura porque está siendo usada por ' . $result['total'] . ' película(s)');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al verificar la censura: ' . $e->getMessage());
    redirect('index.php');
}

// Eliminar censura
try {
    $stmt = $db->prepare("DELETE FROM tbl_censura WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Censura eliminada correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar la censura: ' . $e->getMessage());
}

redirect('index.php');
