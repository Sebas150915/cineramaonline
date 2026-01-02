<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Get user data
$stmt = $db->prepare("SELECT * FROM tbl_usuarios WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit;
}

// Get locales
$locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error de seguridad token.');
    } else {
        $usuario = sanitize($_POST['usuario']);
        $nombre = sanitize($_POST['nombre']);
        $rol = sanitize($_POST['rol']);
        $id_local = !empty($_POST['id_local']) ? (int)$_POST['id_local'] : null;
        $estado = isset($_POST['estado']) ? '1' : '0';
        $permiso_boleteria = isset($_POST['permiso_boleteria']) ? 1 : 0;
        $permiso_dulceria = isset($_POST['permiso_dulceria']) ? 1 : 0;
        $password = $_POST['password'];

        if (empty($usuario) || empty($nombre) || empty($rol)) {
            showAlert('error', 'Error', 'Faltan datos obligatorios');
        } else {
            try {
                // Update with or without password
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE tbl_usuarios SET usuario=?, password=?, nombre=?, rol=?, id_local=?, estado=?, permiso_boleteria=?, permiso_dulceria=? WHERE id=?";
                    $params = [$usuario, $hash, $nombre, $rol, $id_local, $estado, $permiso_boleteria, $permiso_dulceria, $id];
                } else {
                    $sql = "UPDATE tbl_usuarios SET usuario=?, nombre=?, rol=?, id_local=?, estado=?, permiso_boleteria=?, permiso_dulceria=? WHERE id=?";
                    $params = [$usuario, $nombre, $rol, $id_local, $estado, $permiso_boleteria, $permiso_dulceria, $id];
                }

                $update = $db->prepare($sql);
                $update->execute($params);

                showAlert('success', 'Éxito', 'Usuario actualizado correctamente');
                // Refresh data
                $stmt->execute([$id]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                showAlert('error', 'Error', 'Error al actualizar');
            }
        }
    }
}

$page_title = "Editar Usuario";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Usuario</h1>
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
                    <input type="text" name="usuario" class="form-control" value="<?php echo htmlspecialchars($user['usuario']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Contraseña (Dejar en blanco para mantener actual)</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label class="required">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="required">Rol</label>
                    <select name="rol" id="select-rol" class="form-control" required>
                        <option value="supervisor" <?php echo $user['rol'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                        <option value="ventas" <?php echo $user['rol'] == 'ventas' ? 'selected' : ''; ?>>Ventas</option>
                        <option value="superadmin" <?php echo $user['rol'] == 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                    </select>
                </div>

                <div class="form-group" id="group-local">
                    <label class="required">Cine Asignado</label>
                    <select name="id_local" class="form-control">
                        <option value="">-- Seleccionar Cine --</option>
                        <?php foreach ($locales as $local): ?>
                            <option value="<?php echo $local['id']; ?>" <?php echo $user['id_local'] == $local['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($local['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="d-block mb-10">Permisos de Venta</label>
                    <div class="checkbox-wrapper mb-10">
                        <input type="checkbox" name="permiso_boleteria" id="permiso_boleteria" value="1" <?php echo $user['permiso_boleteria'] ? 'checked' : ''; ?>>
                        <label for="permiso_boleteria">Puede vender Entradas (Boletería)</label>
                    </div>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="permiso_dulceria" id="permiso_dulceria" value="1" <?php echo $user['permiso_dulceria'] ? 'checked' : ''; ?>>
                        <label for="permiso_dulceria">Puede vender Dulcería</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="estado" value="1" <?php echo $user['estado'] == '1' ? 'checked' : ''; ?>> Activo
                    </label>
                </div>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar Usuario</button>
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