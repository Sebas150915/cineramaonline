<?php
require_once '../../config/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $db->prepare("DELETE FROM tbl_cartelera WHERE id = ?");
        $stmt->execute([$id]);

        showAlert('success', 'Éxito', 'Programación eliminada correctamente');
    } catch (PDOException $e) {
        showAlert('error', 'Error', 'Error al eliminar: ' . $e->getMessage());
    }
}

redirect('index.php');
