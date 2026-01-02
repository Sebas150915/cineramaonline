<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Get locales for dropdown
$locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error de seguridad token. Recarga la página.');
    } else {
        $usuario = sanitize($_POST['usuario']);
        $password = $_POST['password']; // Will hash this
        $nombre = sanitize($_POST['nombre']);
        $rol = sanitize($_POST['rol']);
        $id_local = !empty($_POST['id_local']) ? (int)$_POST['id_local'] : null;
        $estado = isset($_POST['estado']) ? '1' : '0';
        $permiso_boleteria = isset($_POST['permiso_boleteria']) ? 1 : 0;
        $permiso_dulceria = isset($_POST['permiso_dulceria']) ? 1 : 0;

        // Validations
        if (empty($usuario) || empty($password) || empty($nombre) || empty($rol)) {
            showAlert('error', 'Error', 'Faltan datos obligatorios');
        } else {
            // Check dupes
            $check = $db->prepare("SELECT id FROM tbl_usuarios WHERE usuario = ?");
            $check->execute([$usuario]);
            if ($check->rowCount() > 0) {
                showAlert('error', 'Error', 'El usuario ya existe');
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO tbl_usuarios (usuario, password, nombre, rol, id_local, estado, permiso_boleteria, permiso_dulceria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$usuario, $hash, $nombre, $rol, $id_local, $estado, $permiso_boleteria, $permiso_dulceria]);

                    showAlert('success', 'Éxito', 'Usuario creado correctamente');
                    redirect('index.php');
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    showAlert('error', 'Error', 'Error al crear usuario');
                }
            }
        }
    }
}

$page_title = "Nuevo Usuario";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nuevo Usuario</h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
    </div>

    <div class="card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="grid-form">
                <div class="form-group">
                    <label class="required">Usuario (Login)</label>
                    <input type="text" name="usuario" class="form-control" require autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="required">Contraseña</label>
                    <input type="password" name="password" class="form-control" require autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label class="required">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" require>
                </div>

                <div class="form-group">
                    <label class="required">Rol</label>
                    <select name="rol" id="select-rol" class="form-control" require>
                        <option value="supervisor">Supervisor</option>
                        <option value="ventas">Ventas</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <div class="form-group" id="group-local">
                    <label class="required">Cine Asignado</label>
                    <select name="id_local" class="form-control">
                        <option value="">-- Seleccionar Cine --</option>
                        <?php foreach ($locales as $local): ?>
                            <option value="<?php echo $local['id']; ?>"><?php echo htmlspecialchars($local['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">Requerido para Supervisor y Ventas</small>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="d-block mb-10">Permisos de Venta</label>
                    <div class="checkbox-wrapper mb-10">
                        <input type="checkbox" name="permiso_boleteria" id="permiso_boleteria" value="1">
                        <label for="permiso_boleteria">Puede vender Entradas (Boletería)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="permiso_dulceria" id="permiso_dulceria" value="1">
                        <label for="permiso_dulceria">Puede vender Dulcería</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" checked> Activo
                    </label>
                </div>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Usuario</button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rolSelect = document.getElementById('select-rol');
        const localGroup = document.getElementById('group-local');

        function toggleLocal() {
            if (rolSelect.value === 'superadmin') {
                localGroup.style.display = 'none';
            } else {
                localGroup.style.display = 'block';
            }
        }

        rolSelect.addEventListener('change', toggleLocal);
        toggleLocal();
    });
</script>

<?php include '../../includes/footer.php'; ?>