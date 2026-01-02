<?php
require_once '../../config/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // Obtener imagen para eliminarla
        $stmt = $db->prepare("SELECT img FROM tbl_slider WHERE id = ?");
        $stmt->execute([$id]);
        $slider = $stmt->fetch();

        if ($slider) {
            // Eliminar registro
            $stmt = $db->prepare("DELETE FROM tbl_slider WHERE id = ?");
            $stmt->execute([$id]);

            // Eliminar archivo de imagen
            if ($slider['img']) {
                deleteImage($slider['img'], 'uploads/sliders/');
            }

            showAlert('success', 'Éxito', 'Slider eliminado correctamente');
        }
    } catch (PDOException $e) {
        showAlert('error', 'Error', 'Error al eliminar el slider: ' . $e->getMessage());
    }
}

redirect('index.php');
