<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Eliminar horario
try {
    $stmt = $db->prepare("DELETE FROM tbl_hora WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Horario eliminado correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar el horario: ' . $e->getMessage());
}

redirect('index.php');
