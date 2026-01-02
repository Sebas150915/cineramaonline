<?php
require_once '../../config/config.php';

$page_title = "Gestión de Cines";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todos los cines con información de empresa
try {
    $stmt = $db->query("
        SELECT l.*, e.razon_social as empresa_nombre 
        FROM tbl_locales l
        LEFT JOIN tbl_empresa e ON l.empresa = e.id
        ORDER BY l.orden ASC, l.id DESC
    ");
    $cines = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener los cines: ' . $e->getMessage());
    $cines = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Cines / Locales</h1>
            <p class="page-subtitle">Gestiona los cines y locales de Cinerama</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Cine
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Empresa</th>
                    <th>Venta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cines as $cine): ?>
                    <tr>
                        <td><strong><?php echo $cine['orden']; ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($cine['nombre']); ?></strong>
                            <?php if ($cine['img']): ?>
                                <br><small style="color: #666;"><i class="fas fa-image"></i> Con imagen</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($cine['direccion']); ?></td>
                        <td><?php echo htmlspecialchars($cine['empresa_nombre']); ?></td>
                        <td>
                            <?php if ($cine['venta'] == 'SI'): ?>
                                <span style="background: #28a745; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px;">
                                    <i class="fas fa-check"></i> SÍ
                                </span>
                            <?php else: ?>
                                <span style="background: #6c757d; color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px;">
                                    <i class="fas fa-times"></i> NO
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cine['estado'] == '1'): ?>
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
                            <a href="../salas/index.php?local=<?php echo $cine['id']; ?>" class="btn btn-primary btn-sm" title="Ver Salas">
                                <i class="fas fa-door-open"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $cine['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $cine['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($cine['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($cine['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $cine['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($cine['nombre']); ?>"
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