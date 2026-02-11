<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Permissions
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    try {
        // Fetch product to get image filename
        $stmt_img = $db->prepare("SELECT imagen FROM tbl_productos WHERE id = ?");
        $stmt_img->execute([$id]);
        $prod = $stmt_img->fetch();

        if ($prod && !empty($prod['imagen'])) {
            deleteImage($prod['imagen'], 'uploads/productos/');
        }

        $stmt = $db->prepare("DELETE FROM tbl_productos WHERE id = ?");
        $stmt->execute([$id]);
        showAlert('success', 'Éxito', 'Producto eliminado.');
    } catch (PDOException $e) {
        // Likely FK constraint if cascade wasn't set or sales records exist
        error_log($e->getMessage());
        showAlert('error', 'Error', 'No se puede eliminar este producto (puede tener registros vinculados).');
    }
}

redirect('index.php');
