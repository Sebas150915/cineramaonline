<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Verify permission
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_title = "Gestión de Series";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Fetch series with user names
$sql = "SELECT s.*, u.usuario as usuario_nombre 
        FROM tbl_series s 
        LEFT JOIN tbl_usuarios u ON s.id_usuario = u.id 
        ORDER BY s.serie ASC";
$series = $db->query($sql)->fetchAll();
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Series de Facturación</h1>
            <p class="page-subtitle">Asignación de series por usuario</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Serie
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Serie</th>
                    <th>Tipo</th>
                    <th>Correlativo Actual</th>
                    <th>Usuario Asignado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($series as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['serie']); ?></strong></td>
                        <td>
                            <?php if ($item['tipo'] == 'B'): ?>
                                <span class="badge badge-primary">Boleta</span>
                            <?php else: ?>
                                <span class="badge badge-info">Factura</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo str_pad($item['correlativo'], 8, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <?php if ($item['usuario_nombre']): ?>
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['usuario_nombre']); ?>
                            <?php else: ?>
                                <span class="text-muted">-- Sin asignar --</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['estado'] == '1'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
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