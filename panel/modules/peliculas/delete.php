<?php
require_once '../../config/config.php';

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener película
try {
    $stmt = $db->prepare("SELECT * FROM tbl_pelicula WHERE id = ?");
    $stmt->execute([$id]);
    $pelicula = $stmt->fetch();

    if (!$pelicula) {
        showAlert('error', 'Error', 'Película no encontrada');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener la película: ' . $e->getMessage());
    redirect('index.php');
}

// Eliminar película
try {
    // Eliminar imagen si existe
    if (!empty($pelicula['img'])) {
        deleteImage($pelicula['img'], 'uploads/peliculas/');
    }

    $stmt = $db->prepare("DELETE FROM tbl_pelicula WHERE id = ?");
    $stmt->execute([$id]);

    showAlert('success', 'Éxito', 'Película eliminada correctamente');
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al eliminar la película: ' . $e->getMessage());
}

redirect('index.php');
