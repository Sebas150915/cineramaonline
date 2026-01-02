<?php
require_once '../../config/config.php';

$page_title = "Gestión de Películas";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todas las películas con información relacionada
try {
    $stmt = $db->query("
        SELECT p.*, 
               g.nombre as genero_nombre, 
               c.nombre as censura_nombre,
               c.codigo as censura_codigo,
               d.nombre as distribuidora_nombre
        FROM tbl_pelicula p
        LEFT JOIN tbl_genero g ON p.genero = g.id
        LEFT JOIN tbl_censura c ON p.censura = c.id
        LEFT JOIN tbl_distribuidora d ON p.distribuidora = d.id
        ORDER BY p.id DESC
    ");
    $peliculas = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener las películas: ' . $e->getMessage());
    $peliculas = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Películas</h1>
            <p class="page-subtitle">Gestiona el catálogo de películas</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Película
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Título</th>
                    <th>Género</th>
                    <th>Censura</th>
                    <th>Distribuidora</th>
                    <th>Duración</th>
                    <th>Estreno</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($peliculas as $pelicula): ?>
                    <tr>
                        <td><?php echo $pelicula['id']; ?></td>
                        <td>
                            <?php if ($pelicula['img']): ?>
                                <img src="<?php echo UPLOADS_URL . 'peliculas/' . $pelicula['img']; ?>"
                                    alt="Poster"
                                    style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 40px; height: 60px; background: #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-film" style="color: #999;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($pelicula['nombre']); ?></strong>
                            <?php if ($pelicula['trailer']): ?>
                                <br><small style="color: #666;"><i class="fab fa-youtube"></i> <?php echo $pelicula['trailer']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($pelicula['genero_nombre']); ?></td>
                        <td>
                            <span style="background: #0066cc; color: white; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px;">
                                <?php echo htmlspecialchars($pelicula['censura_codigo']); ?>
                            </span>
                        </td>
                        <td><small><?php echo htmlspecialchars($pelicula['distribuidora_nombre']); ?></small></td>
                        <td><?php echo $pelicula['duracion']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($pelicula['fecha_estreno'])); ?></td>
                        <td>
                            <?php if ($pelicula['estado'] == '1'): ?>
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
                            <a href="edit.php?id=<?php echo $pelicula['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="toggle_status.php?id=<?php echo $pelicula['id']; ?>"
                                class="btn btn-secondary btn-sm btn-toggle-status"
                                title="<?php echo ($pelicula['estado'] == '1') ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo ($pelicula['estado'] == '1') ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $pelicula['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($pelicula['nombre']); ?>"
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