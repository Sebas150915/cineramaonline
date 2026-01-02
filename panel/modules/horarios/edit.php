<?php
require_once '../../config/config.php';

$page_title = "Editar Horario";

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener horario
try {
    $stmt = $db->prepare("SELECT * FROM tbl_hora WHERE id = ?");
    $stmt->execute([$id]);
    $horario = $stmt->fetch();

    if (!$horario) {
        showAlert('error', 'Error', 'Horario no encontrado');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener el horario: ' . $e->getMessage());
    redirect('index.php');
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hora = sanitize($_POST['hora']);

    // Validar
    if (empty($hora)) {
        showAlert('error', 'Error', 'El horario es obligatorio');
    } else {
        try {
            // Verificar si ya existe (excepto el actual)
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM tbl_hora WHERE hora = ? AND id != ?");
            $stmt->execute([$hora, $id]);
            $existe = $stmt->fetch()['total'];

            if ($existe > 0) {
                showAlert('warning', 'Advertencia', 'Este horario ya existe');
            } else {
                $stmt = $db->prepare("UPDATE tbl_hora SET hora = ? WHERE id = ?");
                $stmt->execute([$hora, $id]);

                showAlert('success', 'Éxito', 'Horario actualizado correctamente');
                redirect('index.php');
            }
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al actualizar el horario: ' . $e->getMessage());
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
            <h1 class="page-title">Editar Horario</h1>
            <p class="page-subtitle">Modifica el horario de proyección</p>
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
                    value="<?php echo $horario['hora']; ?>">
                <small class="form-help">Selecciona el horario en formato 24 horas. Intervalos de 15 minutos.</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Horario
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>