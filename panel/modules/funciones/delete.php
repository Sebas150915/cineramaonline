<?php
require_once '../../config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM tbl_funciones WHERE id = ?");
        $stmt->execute([$id]);

        showAlert('success', 'Éxito', 'Función eliminada correctamente');
    } catch (PDOException $e) {
        // Mejor manejo de error si hay integridad referencial (e.g. tickets vendidos)
        if ($e->getCode() == '23000') {
            showAlert('error', 'Error', 'No se puede eliminar esta función porque tiene registros asociados (ventas, etc).');
        } else {
            showAlert('error', 'Error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}

redirect('index.php');
