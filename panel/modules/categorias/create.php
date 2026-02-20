<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error seguridad token.');
    } else {
        $nombre = sanitize($_POST['nombre']);
        $estado = isset($_POST['estado']) ? '1' : '0';

        if (empty($nombre)) {
            showAlert('error', 'Error', 'El nombre es obligatorio');
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO tbl_categoria (nombre, estado) VALUES (?, ?)");
                $stmt->execute([$nombre, $estado]);
                showAlert('success', 'Éxito', 'Categoría creada correctamente');
                redirect('index.php');
            } catch (PDOException $e) {
                showAlert('error', 'Error', 'Error al crear la categoría: ' . $e->getMessage());
            }
        }
    }
}

$page_title = "Nueva Categoría";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nueva Categoría</h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label class="required">Nombre de la Categoría</label>
                    <input type="text" name="nombre" class="form-control" required autocomplete="off" autofocus>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" checked> Activo
                    </label>
                </div>

                <div class="mt-20">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>