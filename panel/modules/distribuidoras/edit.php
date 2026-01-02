<?php
require_once '../../config/config.php';

$page_title = "Editar Distribuidora";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener distribuidora
try {
    $stmt = $db->prepare("SELECT * FROM tbl_distribuidora WHERE id = ?");
    $stmt->execute([$id]);
    $distribuidora = $stmt->fetch();

    if (!$distribuidora) {
        showAlert('error', 'Error', 'Distribuidora no encontrada');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener la distribuidora: ' . $e->getMessage());
    redirect('index.php');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Validar
    if (empty($nombre)) {
        showAlert('error', 'Error', 'El nombre de la distribuidora es obligatorio');
    } else {
        try {
            $stmt = $db->prepare("UPDATE tbl_distribuidora SET nombre = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nombre, $estado, $id]);

            showAlert('success', 'Éxito', 'Distribuidora actualizada correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar la distribuidora: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Distribuidora</h1>
            <p class="page-subtitle">Modifica la información de la distribuidora</p>
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
                <label class="required">Nombre de la Distribuidora</label>
                <input type="text" name="nombre" class="form-control" required
                    placeholder="Ej: ANDES FILMS, DIAMOND FILMS, WALT DISNEY STUDIOS..."
                    value="<?php echo htmlspecialchars($distribuidora['nombre']); ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($distribuidora['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Distribuidora
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>