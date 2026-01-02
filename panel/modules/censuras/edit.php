<?php
require_once '../../config/config.php';

$page_title = "Editar Censura";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener censura
try {
    $stmt = $db->prepare("SELECT * FROM tbl_censura WHERE id = ?");
    $stmt->execute([$id]);
    $censura = $stmt->fetch();

    if (!$censura) {
        showAlert('error', 'Error', 'Censura no encontrada');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener la censura: ' . $e->getMessage());
    redirect('index.php');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $codigo = sanitize($_POST['codigo']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Validar
    if (empty($nombre) || empty($codigo)) {
        showAlert('error', 'Error', 'El nombre y código son obligatorios');
    } else {
        try {
            $stmt = $db->prepare("UPDATE tbl_censura SET nombre = ?, codigo = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nombre, $codigo, $estado, $id]);

            showAlert('success', 'Éxito', 'Censura actualizada correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar la censura: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Censura</h1>
            <p class="page-subtitle">Modifica la información de la censura</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group">
                <label class="required">Nombre de la Censura</label>
                <input type="text" name="nombre" class="form-control" required
                    placeholder="Ej: TODO ESPECTADOR, MAYORES DE 14..."
                    value="<?php echo htmlspecialchars($censura['nombre']); ?>">
            </div>

            <div class="form-group">
                <label class="required">Código</label>
                <input type="text" name="codigo" class="form-control" required maxlength="3"
                    placeholder="Ej: APT, +14, +18..."
                    value="<?php echo htmlspecialchars($censura['codigo']); ?>">
                <small class="form-help">Máximo 3 caracteres</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($censura['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Censura
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>