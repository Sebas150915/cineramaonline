<?php
require_once '../../config/config.php';

$page_title = "Nuevo Cine";

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
    $img = '';
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $upload_result = uploadImage($_FILES['img'], 'uploads/cines/');
        if ($upload_result['success']) {
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
                INSERT INTO tbl_locales (nombre, descripcion, direccion, ubigeo, cc, estado, empresa, img, orden, venta) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $descripcion, $direccion, $ubigeo, $cc, $estado, $empresa, $img, $orden, $venta]);

            showAlert('success', 'Éxito', 'Cine creado correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear el cine: ' . $e->getMessage());
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
            <h1 class="page-title">Nuevo Cine</h1>
            <p class="page-subtitle">Agrega un nuevo cine o local</p>
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
                        placeholder="Ej: CINERAMA PACIFICO"
                        value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" class="form-control"
                        placeholder="Descripción breve"
                        value="<?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="required">Dirección</label>
                <input type="text" name="direccion" class="form-control" required
                    placeholder="Dirección completa del cine"
                    value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Ubigeo</label>
                    <input type="text" name="ubigeo" class="form-control" required maxlength="6"
                        placeholder="Código ubigeo (6 dígitos)"
                        value="<?php echo isset($_POST['ubigeo']) ? htmlspecialchars($_POST['ubigeo']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="required">Código CC</label>
                    <input type="text" name="cc" class="form-control" required maxlength="4"
                        placeholder="Código centro comercial"
                        value="<?php echo isset($_POST['cc']) ? htmlspecialchars($_POST['cc']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="required">Orden</label>
                    <input type="number" name="orden" class="form-control" required min="1"
                        placeholder="Orden de visualización"
                        value="<?php echo isset($_POST['orden']) ? $_POST['orden'] : '1'; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="required">Empresa</label>
                <select name="empresa" class="form-control" required>
                    <option value="">Seleccionar empresa...</option>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo $empresa['id']; ?>"
                            <?php echo (isset($_POST['empresa']) && $_POST['empresa'] == $empresa['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($empresa['razon_social']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Imagen del Cine</label>
                <input type="file" name="img" class="form-control" accept="image/*">
                <small class="form-help">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 5MB</small>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="venta" value="SI">
                        Habilitado para venta
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" checked>
                        Activo
                    </label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cine
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>