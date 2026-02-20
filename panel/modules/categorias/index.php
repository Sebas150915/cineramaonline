<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Check permissions - using 'permiso_dulceria' or generic admin role
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_title = "Gestión de Categorías";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todas las categorías
try {
    $stmt = $db->query("SELECT * FROM tbl_categoria ORDER BY nombre ASC");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener las categorías: ' . $e->getMessage());
    $categorias = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Categorías de Productos</h1>
            <p class="page-subtitle">Gestiona las categorías para la dulcería</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Categoría
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                        <td>
                            <?php if ($cat['estado'] == '1'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $cat['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($cat['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($cat['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $cat['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($cat['nombre']); ?>"
                                title="Eliminar"
                                onclick="return confirm('¿Estás seguro de eliminar esta categoría?');">
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
        
        // Handle toggle status with AJAX like in other modules if implemented, 
        // or just rely on the link reloading. Ideally we standadize.
        $(".btn-toggle-status").on("click", function(e) {
            e.preventDefault();
            const btn = $(this);
            const url = btn.attr("href");
            
            $.get(url, function(data) {
                // Assuming the backend returns JSON, if not we might need to reload or handle differently.
                // The prompt template used generic PHP redirect or JSON? The provided template output JSON.
                try {
                   const res = typeof data === "string" ? JSON.parse(data) : data;
                   if(res.success) {
                       location.reload();
                   } else {
                       alert(res.message);
                   }
                } catch(e) {
                    location.reload(); 
                }
            });
        });
    });
</script>
';
include '../../includes/footer.php'; ?>