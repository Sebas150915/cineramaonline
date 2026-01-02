<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM tbl_series WHERE id = ?");
$stmt->execute([$id]);
$serie = $stmt->fetch();
if (!$serie) {
    header("Location: index.php");
    exit;
}

$users = $db->query("SELECT id, usuario, nombre FROM tbl_usuarios WHERE estado = '1'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error seguridad token.');
    } else {
        $serie_num = sanitize($_POST['serie']);
        $tipo = sanitize($_POST['tipo']);
        $correlativo = (int)$_POST['correlativo'];
        $id_usuario = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
        $estado = isset($_POST['estado']) ? '1' : '0';

        if (empty($serie_num) || empty($tipo)) {
            showAlert('error', 'Error', 'Datos incompletos');
        } else {
            try {
                $upd = $db->prepare("UPDATE tbl_series SET serie=?, tipo=?, correlativo=?, id_usuario=?, estado=? WHERE id=?");
                $upd->execute([$serie_num, $tipo, $correlativo, $id_usuario, $estado, $id]);
                showAlert('success', 'Éxito', 'Serie actualizada.');

                $stmt->execute([$id]);
                $serie = $stmt->fetch();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                showAlert('error', 'Error', 'Error al actualizar.');
            }
        }
    }
}

$page_title = "Editar Serie";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Serie: <?php echo htmlspecialchars($serie['serie']); ?></h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="grid-form">
                <div class="form-group">
                    <label class="required">Número de Serie</label>
                    <input type="text" name="serie" class="form-control" value="<?php echo htmlspecialchars($serie['serie']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="B" <?php echo $serie['tipo'] == 'B' ? 'selected' : ''; ?>>Boleta (B)</option>
                        <option value="F" <?php echo $serie['tipo'] == 'F' ? 'selected' : ''; ?>>Factura (F)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Correlativo Actual</label>
                    <input type="number" name="correlativo" class="form-control" value="<?php echo $serie['correlativo']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Asignar a Usuario</label>
                    <select name="id_usuario" class="form-control">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $serie['id_usuario'] == $u['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['usuario'] . ' - ' . $u['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" <?php echo $serie['estado'] == '1' ? 'checked' : ''; ?>> Activo
                    </label>
                </div>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
            </div>
        </form>
    </div>
</main>
<?php include '../../includes/footer.php'; ?>