<?php
require_once '../../config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM tbl_tarifa WHERE id = ?");
        $stmt->execute([$id]);
        showAlert('success', 'Éxito', 'Tarifa eliminada');
    } catch (PDOException $e) {
        showAlert('error', 'Error', 'No se puede eliminar: ' . $e->getMessage());
    }
}
redirect('index.php');
