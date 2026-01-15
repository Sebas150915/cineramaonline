<?php
require_once '../../config/config.php';

$page_title = "Editar Slider";

// Verificar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];

// Obtener dato
try {
    $stmt = $db->prepare("SELECT * FROM tbl_slider WHERE id = ?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch();

    if (!$slider) {
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener el slider: ' . $e->getMessage());
    $slider = null;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $slider) {
    $titulo = sanitize($_POST['titulo']);
    $link = sanitize($_POST['link']);
    $orden = (int)$_POST['orden'];
    $estado = isset($_POST['estado']) ? '1' : '0';
    $img = $slider['img'];

    // Manejar nueva imagen
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = resizeSliderImage($_FILES['img'], 'uploads/sliders/');
        if ($upload_result['success']) {
            // Eliminar imagen anterior si existe
            if ($slider['img']) {
                deleteImage($slider['img'], 'uploads/sliders/');
            }
            $img = $upload_result['filename'];
        } else {
            showAlert('error', 'Error', $upload_result['message']);
        }
    }

    if (empty($titulo)) {
        showAlert('error', 'Error', 'El título es obligatorio');
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE tbl_slider 
                SET titulo = ?, link = ?, orden = ?, img = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([$titulo, $link, $orden, $img, $estado, $id]);

            showAlert('success', 'Éxito', 'Slider actualizado correctamente');
            // Recargar datos
            $stmt = $db->prepare("SELECT * FROM tbl_slider WHERE id = ?");
            $stmt->execute([$id]);
            $slider = $stmt->fetch();
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar el slider: ' . $e->getMessage());
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Slider</h1>
            <p class="page-subtitle">Modifica la información del banner</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="" enctype="multipart/form-data">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Información del Banner</h3>

            <div class="form-group">
                <label class="required">Título</label>
                <input type="text" name="titulo" class="form-control" required
                    placeholder="Título del banner"
                    value="<?php echo htmlspecialchars($slider['titulo']); ?>">
            </div>

            <div class="form-group">
                <label>Enlace (Link)</label>
                <input type="text" name="link" class="form-control"
                    placeholder="URL de destino"
                    value="<?php echo htmlspecialchars($slider['link']); ?>">
            </div>

            <div class="form-group">
                <label>Orden</label>
                <input type="number" name="orden" class="form-control"
                    value="<?php echo $slider['orden']; ?>">
            </div>

            <hr style="margin: 30px 0;">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Imagen y Estado</h3>

            <div class="form-group">
                <label>Imagen Actual</label>
                <?php if ($slider['img']): ?>
                    <div style="margin: 10px 0;">
                        <img src="<?php echo UPLOADS_URL . 'sliders/' . $slider['img']; ?>"
                            style="max-width: 300px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>

                <label>Cambiar Imagen</label>
                <input type="file" name="img" class="form-control" accept="image/*">
                <small class="form-help">Dejar vacío para mantener la imagen actual. La nueva imagen será redimensionada a 1920x560px</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($slider['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo (visible en el sitio)
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>