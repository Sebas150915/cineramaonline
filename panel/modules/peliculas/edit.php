<?php
require_once '../../config/config.php';

$page_title = "Editar Película";

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

// Obtener datos para los selects
try {
    $generos = $db->query("SELECT id, nombre FROM tbl_genero WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $censuras = $db->query("SELECT id, nombre, codigo FROM tbl_censura WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $distribuidoras = $db->query("SELECT id, nombre FROM tbl_distribuidora WHERE estado = '1' ORDER BY nombre")->fetchAll();
} catch (PDOException $e) {
    $generos = $censuras = $distribuidoras = [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $genero = (int)$_POST['genero'];
    $censura = (int)$_POST['censura'];
    $distribuidora = (int)$_POST['distribuidora'];
    $director = sanitize($_POST['director']);
    $reparto = sanitize($_POST['reparto']);
    $sinopsis = sanitize($_POST['sinopsis']);
    $duracion = sanitize($_POST['duracion']);
    $trailer = sanitize($_POST['trailer']);
    $fecha_estreno = sanitize($_POST['fecha_estreno']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Manejar imagen
    $img = $pelicula['img']; // Mantener imagen actual
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = uploadImage($_FILES['img'], 'uploads/peliculas/');
        if ($upload_result['success']) {
            // Eliminar imagen anterior
            if (!empty($pelicula['img'])) {
                deleteImage($pelicula['img'], 'uploads/peliculas/');
            }
            $img = $upload_result['filename'];
        } else {
            showAlert('error', 'Error', $upload_result['message']);
        }
    }

    // Validar
    if (empty($nombre) || $genero <= 0 || $censura <= 0 || $distribuidora <= 0 || empty($fecha_estreno)) {
        showAlert('error', 'Error', 'Todos los campos obligatorios deben ser completados');
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE tbl_pelicula 
                SET nombre = ?, genero = ?, censura = ?, distribuidora = ?, director = ?, 
                    reparto = ?, sinopsis = ?, duracion = ?, trailer = ?, fecha_estreno = ?, img = ?, estado = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $genero, $censura, $distribuidora, $director, $reparto, $sinopsis, $duracion, $trailer, $fecha_estreno, $img, $estado, $id]);

            showAlert('success', 'Éxito', 'Película actualizada correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar la película: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Película</h1>
            <p class="page-subtitle">Modifica la información de la película</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="" enctype="multipart/form-data">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Información Básica</h3>

            <div class="form-group">
                <label class="required">Título de la Película</label>
                <input type="text" name="nombre" class="form-control" required
                    value="<?php echo htmlspecialchars($pelicula['nombre']); ?>">
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Género</label>
                    <select name="genero" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($generos as $gen): ?>
                            <option value="<?php echo $gen['id']; ?>"
                                <?php echo ($pelicula['genero'] == $gen['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($gen['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Censura</label>
                    <select name="censura" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($censuras as $cen): ?>
                            <option value="<?php echo $cen['id']; ?>"
                                <?php echo ($pelicula['censura'] == $cen['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cen['nombre']) . ' (' . $cen['codigo'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Distribuidora</label>
                    <select name="distribuidora" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($distribuidoras as $dist): ?>
                            <option value="<?php echo $dist['id']; ?>"
                                <?php echo ($pelicula['distribuidora'] == $dist['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dist['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr style="margin: 30px 0;">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Detalles de la Película</h3>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label>Director</label>
                    <input type="text" name="director" class="form-control"
                        value="<?php echo htmlspecialchars($pelicula['director']); ?>">
                </div>

                <div class="form-group">
                    <label>Reparto Principal</label>
                    <input type="text" name="reparto" class="form-control"
                        value="<?php echo htmlspecialchars($pelicula['reparto']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Sinopsis</label>
                <textarea name="sinopsis" class="form-control" rows="4"><?php echo htmlspecialchars($pelicula['sinopsis']); ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label>Duración (HH:MM:SS)</label>
                    <input type="time" name="duracion" class="form-control" step="1"
                        value="<?php echo $pelicula['duracion']; ?>">
                </div>

                <div class="form-group">
                    <label>ID Trailer YouTube</label>
                    <input type="text" name="trailer" class="form-control" maxlength="50"
                        value="<?php echo htmlspecialchars($pelicula['trailer']); ?>">
                </div>

                <div class="form-group">
                    <label class="required">Fecha de Estreno</label>
                    <input type="date" name="fecha_estreno" class="form-control" required
                        value="<?php echo $pelicula['fecha_estreno']; ?>">
                </div>
            </div>

            <hr style="margin: 30px 0;">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Imagen y Estado</h3>

            <div class="form-group">
                <label>Poster de la Película</label>
                <?php if (!empty($pelicula['img'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo UPLOADS_URL . 'peliculas/' . $pelicula['img']; ?>"
                            alt="Poster actual"
                            style="max-width: 200px; border-radius: 8px; border: 2px solid #ddd;">
                        <p style="margin-top: 5px; font-size: 12px; color: #666;">Poster actual</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="img" class="form-control" accept="image/*">
                <small class="form-help">Dejar vacío para mantener el poster actual</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($pelicula['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo (visible en cartelera)
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Película
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>