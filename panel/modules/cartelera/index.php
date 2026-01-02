<?php
require_once '../../config/config.php';

$page_title = "Gestión de Cartelera";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener cartelera con relaciones
try {
    $sql = "SELECT c.*, p.nombre as pelicula_nombre, p.img as pelicula_img, l.nombre as cine_nombre, s.nombre as sala_nombre 
            FROM tbl_cartelera c 
            JOIN tbl_pelicula p ON c.pelicula = p.id 
            JOIN tbl_locales l ON c.local = l.id 
            LEFT JOIN tbl_sala s ON c.sala = s.id 
            ORDER BY c.fecha_inicio DESC, l.nombre ASC";
    $stmt = $db->query($sql);
    $carteleras = $stmt->fetchAll();
} catch (PDOException $e) {
    $carteleras = [];
    showAlert('error', 'Error', 'Error al obtener cartelera: ' . $e->getMessage());
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Cartelera</h1>
            <p class="page-subtitle">Programación de películas en cines</p>
        </div>
        <div>
            <a href="export_excel.php" class="btn btn-success" target="_blank">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Programación
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Película</th>
                    <th>Cine / Sala</th>
                    <th>Fechas</th>
                    <th>Formato</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($carteleras)): ?>
                    <?php foreach ($carteleras as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if ($item['pelicula_img']): ?>
                                        <img src="<?php echo UPLOADS_URL . 'peliculas/' . $item['pelicula_img']; ?>"
                                            style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['pelicula_nombre']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($item['cine_nombre']); ?></div>
                                <small class="text-muted"><?php echo $item['sala_nombre'] ? htmlspecialchars($item['sala_nombre']) : 'Sala no asignada'; ?></small>
                            </td>
                            <td>
                                <div><small>Del: <?php echo formatDate($item['fecha_inicio']); ?></small></div>
                                <div><small>Al: <?php echo formatDate($item['fecha_fin']); ?></small></div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($item['formato']); ?></span>
                                <span class="badge badge-secondary"><?php echo htmlspecialchars($item['idioma']); ?></span>
                            </td>
                            <td>
                                <?php if ($item['estado'] == '1'): ?>
                                    <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Activo</span>
                                <?php else: ?>
                                    <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="generate_functions.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm" title="Generar Funciones" onclick="return confirm('¿Generar funciones para este rango de fechas?')">
                                    <i class="fas fa-magic"></i>
                                </a>
                                <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $item['id']; ?>"
                                    class="btn btn-danger btn-sm btn-delete"
                                    data-name="Programación #<?php echo $item['id']; ?>"
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