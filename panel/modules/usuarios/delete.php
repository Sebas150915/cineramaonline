<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission
if ($_SESSION['rol'] !== 'superadmin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Prevent self-delete
    if ($id == $_SESSION['user_id']) {
        showAlert('error', 'Error', 'No puedes eliminar tu propia cuenta');
    } else {
        try {
            // Delete user
            $stmt = $db->prepare("DELETE FROM tbl_usuarios WHERE id = ?");
            $stmt->execute([$id]);
            showAlert('success', 'Éxito', 'Usuario eliminado correctamente');
        } catch (PDOException $e) {
            error_log($e->getMessage());
            showAlert('error', 'Error', 'Error al eliminar usuario');
        }
    }
}

redirect('index.php');
