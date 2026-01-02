<?php
require_once '../../config/config.php';

$page_title = "Gestión de Sliders";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todos los sliders
try {
    $stmt = $db->query("SELECT * FROM tbl_slider ORDER BY orden ASC, id DESC");
    $sliders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe, podríamos mostrar un mensaje amigable, pero asumiremos que el usuario la creará
    // o que ya existe. Para evitar error fatal si no existe:
    $sliders = [];
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        showAlert('warning', 'Aviso', 'La tabla tbl_slider no existe. Por favor contacte al administrador.');
    } else {
        showAlert('error', 'Error', 'Error al obtener los sliders: ' . $e->getMessage());
    }
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Sliders</h1>
            <p class="page-subtitle">Gestiona los banners del slider principal</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Slider
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Link</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sliders)): ?>
                    <?php foreach ($sliders as $slider): ?>
                        <tr>
                            <td><?php echo $slider['orden']; ?></td>
                            <td>
                                <?php if ($slider['img']): ?>
                                    <img src="<?php echo UPLOADS_URL . 'sliders/' . $slider['img']; ?>"
                                        alt="Slider"
                                        style="width: 100px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 100px; height: 40px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #999;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($slider['titulo']); ?></strong></td>
                            <td><small><?php echo htmlspecialchars($slider['link']); ?></small></td>
                            <td>
                                <?php if ($slider['estado'] == '1'): ?>
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
                                <a href="edit.php?id=<?php echo $slider['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="toggle_status.php?id=<?php echo $slider['id']; ?>"
                                    class="btn btn-secondary btn-sm btn-toggle-status"
                                    title="<?php echo ($slider['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                    <i class="fas fa-<?php echo ($slider['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $slider['id']; ?>"
                                    class="btn btn-danger btn-sm btn-delete"
                                    data-name="<?php echo htmlspecialchars($slider['titulo']); ?>"
                                    title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
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