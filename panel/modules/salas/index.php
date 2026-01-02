<?php
require_once '../../config/config.php';

$page_title = "Gestión de Salas";

// Obtener local si viene por parámetro
$local_id = isset($_GET['local']) ? (int)$_GET['local'] : 0;
$local_nombre = '';

if ($local_id > 0) {
    try {
        $stmt = $db->prepare("SELECT nombre FROM tbl_locales WHERE id = ?");
        $stmt->execute([$local_id]);
        $local_info = $stmt->fetch();
        $local_nombre = $local_info ? $local_info['nombre'] : '';
    } catch (PDOException $e) {
        $local_nombre = '';
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todas las salas con información del local
try {
    if ($local_id > 0) {
        $stmt = $db->prepare("
            SELECT s.*, l.nombre as local_nombre 
            FROM tbl_sala s
            LEFT JOIN tbl_locales l ON s.local = l.id
            WHERE s.local = ?
            ORDER BY s.nombre
        ");
        $stmt->execute([$local_id]);
    } else {
        $stmt = $db->query("
            SELECT s.*, l.nombre as local_nombre 
            FROM tbl_sala s
            LEFT JOIN tbl_locales l ON s.local = l.id
            ORDER BY l.nombre, s.nombre
        ");
    }
    $salas = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener las salas: ' . $e->getMessage());
    $salas = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">
                <?php if ($local_nombre): ?>
                    Salas de <?php echo htmlspecialchars($local_nombre); ?>
                <?php else: ?>
                    Salas de Cine
                <?php endif; ?>
            </h1>
            <p class="page-subtitle">
                <?php if ($local_nombre): ?>
                    Gestiona las salas de este local
                <?php else: ?>
                    Gestiona las salas por local
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if ($local_id > 0): ?>
                <a href="../cines/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Locales
                </a>
                <a href="create.php?local=<?php echo $local_id; ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Sala
                </a>
            <?php else: ?>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Sala
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Local</th>
                    <th>Nombre Sala</th>
                    <th>Capacidad</th>
                    <th>Filas x Columnas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salas as $sala): ?>
                    <tr>
                        <td><?php echo $sala['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($sala['local_nombre']); ?></strong></td>
                        <td>
                            <span style="background: #0066cc; color: white; padding: 6px 12px; border-radius: 6px; font-weight: bold;">
                                <?php echo htmlspecialchars($sala['nombre']); ?>
                            </span>
                        </td>
                        <td><?php echo $sala['capacidad']; ?> asientos</td>
                        <td><?php echo $sala['filas']; ?> x <?php echo $sala['columnas']; ?></td>
                        <td>
                            <?php if ($sala['estado'] == '1'): ?>
                                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                    <i class="fas fa-check-circle"></i> Activo
                                </span>
                            <?php else: ?>
                                <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">
                                    <i class="fas fa-times-circle"></i> Inactivo
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="asientos.php?id=<?php echo $sala['id']; ?>" class="btn btn-primary btn-sm" title="Gestionar Asientos">
                                <i class="fas fa-couch"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $sala['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $sala['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($sala['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($sala['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $sala['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($sala['nombre']); ?>"
                                title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
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