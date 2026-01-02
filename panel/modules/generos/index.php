<?php
require_once '../../config/config.php';

$page_title = "Gestión de Géneros";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todos los géneros
try {
    $stmt = $db->query("SELECT * FROM tbl_genero ORDER BY id DESC");
    $generos = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener los géneros: ' . $e->getMessage());
    $generos = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Géneros de Películas</h1>
            <p class="page-subtitle">Gestiona los géneros cinematográficos</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Género
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($generos as $genero): ?>
                    <tr>
                        <td><?php echo $genero['id']; ?></td>
                        <td><?php echo htmlspecialchars($genero['nombre']); ?></td>
                        <td>
                            <?php if ($genero['estado'] == '1'): ?>
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
                            <a href="edit.php?id=<?php echo $genero['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $genero['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($genero['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($genero['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $genero['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($genero['nombre']); ?>"
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