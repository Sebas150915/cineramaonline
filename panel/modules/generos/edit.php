<?php
require_once '../../config/config.php';

$page_title = "Editar Género";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener género
try {
    $stmt = $db->prepare("SELECT * FROM tbl_genero WHERE id = ?");
    $stmt->execute([$id]);
    $genero = $stmt->fetch();

    if (!$genero) {
        showAlert('error', 'Error', 'Género no encontrado');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener el género: ' . $e->getMessage());
    redirect('index.php');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Validar
    if (empty($nombre)) {
        showAlert('error', 'Error', 'El nombre del género es obligatorio');
    } else {
        try {
            $stmt = $db->prepare("UPDATE tbl_genero SET nombre = ?, estado = ? WHERE id = ?");
            $stmt->execute([$nombre, $estado, $id]);

            showAlert('success', 'Éxito', 'Género actualizado correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar el género: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Género</h1>
            <p class="page-subtitle">Modifica la información del género</p>
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
                <label class="required">Nombre del Género</label>
                <input type="text" name="nombre" class="form-control" required
                    placeholder="Ej: Acción, Comedia, Drama..."
                    value="<?php echo htmlspecialchars($genero['nombre']); ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($genero['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Género
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>