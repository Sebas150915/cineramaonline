<?php
require_once '../../config/config.php';

$page_title = "Gestión de Censuras";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todas las censuras
try {
    $stmt = $db->query("SELECT * FROM tbl_censura ORDER BY id DESC");
    $censuras = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener las censuras: ' . $e->getMessage());
    $censuras = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Clasificaciones de Censura</h1>
            <p class="page-subtitle">Gestiona las clasificaciones por edad de las películas</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Censura
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($censuras as $censura): ?>
                    <tr>
                        <td><?php echo $censura['id']; ?></td>
                        <td><?php echo htmlspecialchars($censura['nombre']); ?></td>
                        <td>
                            <span style="background: #0066cc; color: white; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px;">
                                <?php echo htmlspecialchars($censura['codigo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($censura['estado'] == '1'): ?>
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
                            <a href="edit.php?id=<?php echo $censura['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $censura['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($censura['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($censura['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $censura['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($censura['nombre']); ?>"
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