<?php
require_once '../../config/config.php';

$page_title = "Nuevo Horario";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hora = sanitize($_POST['hora']);

    // Validar
    if (empty($hora)) {
        showAlert('error', 'Error', 'El horario es obligatorio');
    } else {
        try {
            // Verificar si ya existe
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_hora WHERE hora = ?");
            $stmt->execute([$hora]);
            $existe = $stmt->fetch()['total'];

            if ($existe > 0) {
                showAlert('warning', 'Advertencia', 'Este horario ya existe');
            } else {
                $stmt = $db->prepare("INSERT INTO tbl_hora (hora) VALUES (?)");
                $stmt->execute([$hora]);

                showAlert('success', 'Éxito', 'Horario creado correctamente');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear el horario: ' . $e->getMessage());
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
            <h1 class="page-title">Nuevo Horario</h1>
            <p class="page-subtitle">Agrega un nuevo horario de proyección</p>
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
                <label class="required">Horario</label>
                <input type="time" name="hora" class="form-control" required step="900"
                    value="<?php echo isset($_POST['hora']) ? $_POST['hora'] : ''; ?>">
                <small class="form-help">Selecciona el horario en formato 24 horas. Intervalos de 15 minutos.</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Horario
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>