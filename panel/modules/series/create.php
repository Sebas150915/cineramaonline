<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Get users for assignment
$users = $db->query("SELECT id, usuario, nombre FROM tbl_usuarios WHERE estado = '1'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error seguridad token.');
    } else {
        $serie = sanitize($_POST['serie']);
        $tipo = sanitize($_POST['tipo']);
        $correlativo = (int)$_POST['correlativo'];
        $id_usuario = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
        $estado = isset($_POST['estado']) ? '1' : '0';

        if (empty($serie) || empty($tipo)) {
            showAlert('error', 'Error', 'Serie y Tipo requeridos');
        } else {
            // Check dupes
            $check = $db->prepare("SELECT id FROM tbl_series WHERE serie = ?");
            $check->execute([$serie]);
            if ($check->rowCount() > 0) {
                showAlert('error', 'Error', 'Esta serie ya existe');
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO tbl_series (serie, tipo, correlativo, id_usuario, estado) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$serie, $tipo, $correlativo, $id_usuario, $estado]);
                    showAlert('success', 'Éxito', 'Serie creada.');
                    redirect('index.php');
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    showAlert('error', 'Error', 'Error al guardar.');
                }
            }
        }
    }
}

$page_title = "Nueva Serie";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nueva Serie</h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="grid-form">
                <div class="form-group">
                    <label class="required">Número de Serie (Ej: B001, F001)</label>
                    <input type="text" name="serie" class="form-control" required autocomplete="off" uppercase>
                </div>

                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="B">Boleta (B)</option>
                        <option value="F">Factura (F)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Correlativo Inicial</label>
                    <input type="number" name="correlativo" class="form-control" value="0" min="0" required>
                    <small class="form-help">Último número emitido. Usar 0 si es nueva.</small>
                </div>

                <div class="form-group">
                    <label>Asignar a Usuario (Opcional)</label>
                    <select name="id_usuario" class="form-control">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['usuario'] . ' - ' . $u['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" checked> Activo
                    </label>
                </div>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Serie</button>
            </div>
        </form>
    </div>
</main>
<?php include '../../includes/footer.php'; ?>