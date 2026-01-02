<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Verificar si el género está en uso
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_pelicula WHERE genero = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['total'] > 0) {
        showAlert('warning', 'Advertencia', 'No se puede eliminar el género porque está siendo usado por ' . $result['total'] . ' película(s)');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al verificar el género: ' . $e->getMessage());
    redirect('index.php');
}

// Eliminar género
try {
    $stmt = $db->prepare("DELETE FROM tbl_genero WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Género eliminado correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar el género: ' . $e->getMessage());
}

redirect('index.php');
