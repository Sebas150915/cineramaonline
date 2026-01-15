<?php
require_once '../../config/config.php';

$page_title = "Nuevo Slider";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $link = sanitize($_POST['link']);
    $orden = (int)$_POST['orden'];
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Manejar imagen
    $img = '';
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = resizeSliderImage($_FILES['img'], 'uploads/sliders/');
        if ($upload_result['success']) {
            $img = $upload_result['filename'];
        } else {
            showAlert('error', 'Error', $upload_result['message']);
        }
    }

    // Validar campos obligatorios
    if (empty($titulo)) {
        showAlert('error', 'Error', 'El título es obligatorio');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO tbl_slider 
                (titulo, link, orden, img, estado) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$titulo, $link, $orden, $img, $estado]);

            showAlert('success', 'Éxito', 'Slider creado correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear el slider: ' . $e->getMessage());
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
            <h1 class="page-title">Nuevo Slider</h1>
            <p class="page-subtitle">Agrega un nuevo banner al slider</p>
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
                    placeholder="Título del banner (usado para alt text)"
                    value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Enlace (Link)</label>
                <input type="text" name="link" class="form-control"
                    placeholder="Ej: https://cinerama.com.pe/peliculas/detalle.php?id=123"
                    value="<?php echo isset($_POST['link']) ? htmlspecialchars($_POST['link']) : ''; ?>">
                <small class="form-help">Opcional. URL a la que redirige al hacer clic.</small>
            </div>

            <div class="form-group">
                <label>Orden</label>
                <input type="number" name="orden" class="form-control"
                    value="<?php echo isset($_POST['orden']) ? $_POST['orden'] : '0'; ?>">
                <small class="form-help">Menor número aparece primero.</small>
            </div>

            <hr style="margin: 30px 0;">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Imagen y Estado</h3>

            <div class="form-group">
                <label class="required">Imagen del Banner</label>
                <input type="file" name="img" class="form-control" accept="image/*" required>
                <small class="form-help">Formatos: JPG, PNG, WEBP. La imagen será redimensionada a 1920x560px con recorte centrado inteligente</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo (visible en el sitio)
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Slider
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>