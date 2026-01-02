<?php
require_once '../../config/config.php';

$page_title = "Nuevo Género";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Validar
    if (empty($nombre)) {
        showAlert('error', 'Error', 'El nombre del género es obligatorio');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tbl_genero (nombre, estado) VALUES (?, ?)");
            $stmt->execute([$nombre, $estado]);

            showAlert('success', 'Éxito', 'Género creado correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear el género: ' . $e->getMessage());
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
            <h1 class="page-title">Nuevo Género</h1>
            <p class="page-subtitle">Agrega un nuevo género cinematográfico</p>
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
                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Género
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>