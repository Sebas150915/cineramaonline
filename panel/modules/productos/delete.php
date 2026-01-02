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
        // Optional: Check if used in recipes as ingredient
        $check = $db->prepare("SELECT COUNT(*) FROM tbl_recetas WHERE id_producto_hijo = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            // It is used in a recipe. FK is CASCADE, so it would delete it from the recipe.
            // But let's act safe/informative or just allow it.
            // For now, allow it but maybe log it?
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
