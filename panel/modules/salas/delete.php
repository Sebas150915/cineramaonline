<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Eliminar sala
try {
    $stmt = $db->prepare("DELETE FROM tbl_sala WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Sala eliminada correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar la sala: ' . $e->getMessage());
}

redirect('index.php');
