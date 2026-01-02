<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission (only superadmin)
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    // Redirect to dashboard if not authorized
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_title = "Gestión de Usuarios";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Get all users with local name
try {
    $stmt = $db->query("
        SELECT u.*, l.nombre as local_nombre 
        FROM tbl_usuarios u
        LEFT JOIN tbl_locales l ON u.id_local = l.id
        ORDER BY u.id DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener usuarios: ' . $e->getMessage());
    $users = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Gestión de acceso y permisos</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Cine Asignado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['usuario']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['nombre']); ?></td>
                        <td>
                            <?php
                            $badgeClass = 'secondary';
                            if ($user['rol'] == 'superadmin') $badgeClass = 'danger';
                            elseif ($user['rol'] == 'supervisor') $badgeClass = 'primary';
                            elseif ($user['rol'] == 'ventas') $badgeClass = 'info';
                            ?>
                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                <?php echo ucfirst($user['rol']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            if ($user['rol'] == 'superadmin') {
                                echo '<span class="text-muted">Todos (Global)</span>';
                            } else {
                                echo $user['local_nombre'] ? htmlspecialchars($user['local_nombre']) : '<span class="text-danger">Sin asignar</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($user['estado'] == '1'): ?>
                                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Activo</span>
                            <?php else: ?>
                                <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['rol'] !== 'superadmin' || $user['id'] !== $_SESSION['user_id']): ?>
                                <a href="delete.php?id=<?php echo $user['id']; ?>"
                                    class="btn btn-danger btn-sm btn-delete"
                                    data-name="<?php echo htmlspecialchars($user['usuario']); ?>"
                                    title="Eliminar"
                                    onclick="return confirm('¿Estás seguro de eliminar este usuario?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>


<?php
$extra_js = '
<script>
    $(document).ready(function() {
        $(".datatable").DataTable();
    });
</script>
';
include '../../includes/footer.php'; ?>