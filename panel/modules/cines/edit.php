<?php
require_once '../../config/config.php';

$page_title = "Editar Cine";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener cine
try {
    $stmt = $db->prepare("SELECT * FROM tbl_locales WHERE id = ?");
    $stmt->execute([$id]);
    $cine = $stmt->fetch();

    if (!$cine) {
        showAlert('error', 'Error', 'Cine no encontrado');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener el cine: ' . $e->getMessage());
    redirect('index.php');
}

// Obtener empresas para el select
try {
    $stmt = $db->query("SELECT id, razon_social FROM tbl_empresa WHERE estado = '1' ORDER BY razon_social");
    $empresas = $stmt->fetchAll();
} catch (PDOException $e) {
    $empresas = [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $descripcion = sanitize($_POST['descripcion']);
    $direccion = sanitize($_POST['direccion']);
    $ubigeo = sanitize($_POST['ubigeo']);
    $cc = sanitize($_POST['cc']);
    $empresa = (int)$_POST['empresa'];
    $orden = (int)$_POST['orden'];
    $venta = isset($_POST['venta']) ? 'SI' : 'NO';
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Manejar imagen
    $img = $cine['img']; // Mantener imagen actual por defecto
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = uploadImage($_FILES['img'], 'uploads/cines/');
        if ($upload_result['success']) {
            // Eliminar imagen anterior si existe
            if (!empty($cine['img'])) {
                deleteImage($cine['img'], 'uploads/cines/');
            }
            $img = $upload_result['filename'];
        } else {
            showAlert('error', 'Error', $upload_result['message']);
        }
    }

    // Validar
    if (empty($nombre) || empty($direccion) || empty($ubigeo) || empty($cc) || $empresa <= 0) {
        showAlert('error', 'Error', 'Todos los campos obligatorios deben ser completados');
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE tbl_locales 
                SET nombre = ?, descripcion = ?, direccion = ?, ubigeo = ?, cc = ?, 
                    estado = ?, empresa = ?, img = ?, orden = ?, venta = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $descripcion, $direccion, $ubigeo, $cc, $estado, $empresa, $img, $orden, $venta, $id]);

            showAlert('success', 'Éxito', 'Cine actualizado correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar el cine: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Cine</h1>
            <p class="page-subtitle">Modifica la información del cine</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Nombre del Cine</label>
                    <input type="text" name="nombre" class="form-control" required
                        value="<?php echo htmlspecialchars($cine['nombre']); ?>">
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" class="form-control"
                        value="<?php echo htmlspecialchars($cine['descripcion']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="required">Dirección</label>
                <input type="text" name="direccion" class="form-control" required
                    value="<?php echo htmlspecialchars($cine['direccion']); ?>">
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Ubigeo</label>
                    <input type="text" name="ubigeo" class="form-control" required maxlength="6"
                        value="<?php echo htmlspecialchars($cine['ubigeo']); ?>">
                </div>

                <div class="form-group">
                    <label class="required">Código CC</label>
                    <input type="text" name="cc" class="form-control" required maxlength="4"
                        value="<?php echo htmlspecialchars($cine['cc']); ?>">
                </div>

                <div class="form-group">
                    <label class="required">Orden</label>
                    <input type="number" name="orden" class="form-control" required min="1"
                        value="<?php echo $cine['orden']; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="required">Empresa</label>
                <select name="empresa" class="form-control" required>
                    <option value="">Seleccionar empresa...</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo $empresa['id']; ?>"
                            <?php echo ($cine['empresa'] == $empresa['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($empresa['razon_social']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Imagen del Cine</label>
                <?php if (!empty($cine['img'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo UPLOADS_URL . 'cines/' . $cine['img']; ?>"
                            alt="Imagen actual"
                            style="max-width: 200px; border-radius: 8px; border: 2px solid #ddd;">
                        <p style="margin-top: 5px; font-size: 12px; color: #666;">Imagen actual: <?php echo $cine['img']; ?></p>
                    </div>
                <?php endif; ?>
                <input type="file" name="img" class="form-control" accept="image/*">
                <small class="form-help">Dejar vacío para mantener la imagen actual. Formatos: JPG, PNG, GIF, WEBP. Máximo 5MB</small>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="venta" value="SI" <?php echo ($cine['venta'] == 'SI') ? 'checked' : ''; ?>>
                        Habilitado para venta
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" <?php echo ($cine['estado'] == '1') ? 'checked' : ''; ?>>
                        Activo
                    </label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Cine
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>