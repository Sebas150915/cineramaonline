<?php
require_once '../../config/config.php';

$page_title = "Nueva Película";

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
    $img = '';
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = uploadImage($_FILES['img'], 'uploads/peliculas/');
        if ($upload_result['success']) {
            $img = $upload_result['filename'];
        } else {
            showAlert('error', 'Error', $upload_result['message']);
        }
    }

    // Validar campos obligatorios
    if (empty($nombre) || $genero <= 0 || $censura <= 0 || $distribuidora <= 0 || empty($fecha_estreno)) {
        showAlert('error', 'Error', 'Todos los campos obligatorios deben ser completados');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO tbl_pelicula 
                (nombre, genero, censura, distribuidora, director, reparto, sinopsis, duracion, trailer, fecha_estreno, img, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $genero, $censura, $distribuidora, $director, $reparto, $sinopsis, $duracion, $trailer, $fecha_estreno, $img, $estado]);

            showAlert('success', 'Éxito', 'Película creada correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear la película: ' . $e->getMessage());
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
            <h1 class="page-title">Nueva Película</h1>
            <p class="page-subtitle">Agrega una nueva película al catálogo</p>
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
                    placeholder="Título completo de la película"
                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Género</label>
                    <select name="genero" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($generos as $gen): ?>
                            <option value="<?php echo $gen['id']; ?>"
                                <?php echo (isset($_POST['genero']) && $_POST['genero'] == $gen['id']) ? 'selected' : ''; ?>>
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
                                <?php echo (isset($_POST['censura']) && $_POST['censura'] == $cen['id']) ? 'selected' : ''; ?>>
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
                                <?php echo (isset($_POST['distribuidora']) && $_POST['distribuidora'] == $dist['id']) ? 'selected' : ''; ?>>
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
                        placeholder="Nombre del director"
                        value="<?php echo isset($_POST['director']) ? htmlspecialchars($_POST['director']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Reparto Principal</label>
                    <input type="text" name="reparto" class="form-control"
                        placeholder="Actores principales separados por coma"
                        value="<?php echo isset($_POST['reparto']) ? htmlspecialchars($_POST['reparto']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Sinopsis</label>
                <textarea name="sinopsis" class="form-control" rows="4"
                    placeholder="Descripción de la película"><?php echo isset($_POST['sinopsis']) ? htmlspecialchars($_POST['sinopsis']) : ''; ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label>Duración (HH:MM:SS)</label>
                    <input type="time" name="duracion" class="form-control" step="1"
                        value="<?php echo isset($_POST['duracion']) ? $_POST['duracion'] : '01:30:00'; ?>">
                    <small class="form-help">Formato: 01:30:00 (1 hora 30 minutos)</small>
                </div>

                <div class="form-group">
                    <label>ID Trailer YouTube</label>
                    <input type="text" name="trailer" class="form-control" maxlength="50"
                        placeholder="Ej: dQw4w9WgXcQ"
                        value="<?php echo isset($_POST['trailer']) ? htmlspecialchars($_POST['trailer']) : ''; ?>">
                    <small class="form-help">Solo el ID del video, no la URL completa</small>
                </div>

                <div class="form-group">
                    <label class="required">Fecha de Estreno</label>
                    <input type="date" name="fecha_estreno" class="form-control" required
                        value="<?php echo isset($_POST['fecha_estreno']) ? $_POST['fecha_estreno'] : date('Y-m-d'); ?>">
                </div>
            </div>

            <hr style="margin: 30px 0;">
            <h3 style="margin-bottom: 20px; color: var(--cinerama-red);">Imagen y Estado</h3>

            <div class="form-group">
                <label>Poster de la Película</label>
                <input type="file" name="img" class="form-control" accept="image/*">
                <small class="form-help">Formatos: JPG, PNG, GIF, WEBP. Máximo 5MB. Tamaño recomendado: 400x600px</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo (visible en cartelera)
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Película
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>