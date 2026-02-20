<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Category
$stmt = $db->prepare("SELECT * FROM tbl_categoria WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: index.php");
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
                $stmt = $db->prepare("UPDATE tbl_categoria SET nombre = ?, estado = ? WHERE id = ?");
                $stmt->execute([$nombre, $estado, $id]);
                showAlert('success', 'Éxito', 'Categoría actualizada correctamente');
                redirect('index.php');
            } catch (PDOException $e) {
                showAlert('error', 'Error', 'Error al actualizar la categoría: ' . $e->getMessage());
            }
        }
    }
}

$page_title = "Editar Categoría";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Categoría</h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label class="required">Nombre de la Categoría</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($categoria['nombre']); ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" <?php echo $categoria['estado'] == '1' ? 'checked' : ''; ?>> Activo
                    </label>
                </div>

                <div class="mt-20">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>