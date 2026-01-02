<?php
require_once '../../config/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $db->prepare("UPDATE tbl_slider SET estado = IF(estado='1', '0', '1') WHERE id = ?");
        $stmt->execute([$id]);

        showAlert('success', 'Éxito', 'Estado actualizado correctamente');
    } catch (PDOException $e) {
        showAlert('error', 'Error', 'Error al cambiar estado: ' . $e->getMessage());
    }
}

redirect('index.php');
