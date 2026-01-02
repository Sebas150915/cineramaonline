<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener cine
try {
    $stmt = $db->prepare("SELECT * FROM tbl_locales WHERE id = ?");
    $stmt->execute([$id]);
    $cine = $stmt->fetch();

    if (!$cine) {
        showAlert('error', 'Error', 'Cine no encontrado');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener el cine: ' . $e->getMessage());
    redirect('index.php');
}

// Eliminar cine
try {
    // Eliminar imagen si existe
    if (!empty($cine['img'])) {
        deleteImage($cine['img'], 'uploads/cines/');
    }

    $stmt = $db->prepare("DELETE FROM tbl_locales WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Cine eliminado correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar el cine: ' . $e->getMessage());
}

redirect('index.php');
