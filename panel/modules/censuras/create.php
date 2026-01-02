<?php
require_once '../../config/config.php';

$page_title = "Nueva Censura";

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
            $stmt = $db->prepare("INSERT INTO tbl_censura (nombre, codigo, estado) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $codigo, $estado]);

            showAlert('success', 'Éxito', 'Censura creada correctamente');
            redirect('index.php');
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear la censura: ' . $e->getMessage());
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
            <h1 class="page-title">Nueva Censura</h1>
            <p class="page-subtitle">Agrega una nueva clasificación de censura</p>
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
                    value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="required">Código</label>
                <input type="text" name="codigo" class="form-control" required maxlength="3"
                    placeholder="Ej: APT, +14, +18..."
                    value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : ''; ?>">
                <small class="form-help">Máximo 3 caracteres</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Censura
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>