<?php
require_once '../../config/config.php';

$page_title = "Gestión de Funciones";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Filtros
$fecha_filtro = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Obtener funciones
try {
    $stmt = $db->prepare("
        SELECT f.*, p.nombre as pelicula, s.nombre as sala, h.hora, l.nombre as local
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.fecha = ?
        ORDER BY h.hora ASC
    ");
    $stmt->execute([$fecha_filtro]);
    $funciones = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener funciones: ' . $e->getMessage());
    $funciones = [];
}
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Funciones / Proyecciones</h1>
            <p class="page-subtitle">Programación de películas en salas</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Función
            </a>
        </div>
    </div>

    <!-- Filtro de Fecha -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label>Filtrar por Fecha:</label>
                <input type="date" name="fecha" class="form-control" value="<?php echo $fecha_filtro; ?>" onchange="this.form.submit()">
            </div>
            <a href="index.php" class="btn btn-secondary">Hoy</a>
        </form>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Hora</th>
                    <th>Película</th>
                    <th>Sala / Cine</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funciones as $func): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--cinerama-red); font-size: 1.1em;">
                                <?php echo date('h:i A', strtotime($func['hora'])); ?>
                            </strong>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($func['pelicula']); ?></strong>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($func['sala']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($func['local']); ?></small>
                        </td>
                        <td>
                            <?php if ($func['estado'] == '1'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $func['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $func['id']; ?>" class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($func['pelicula']); ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <?php
        $extra_js = '
<script>
    $(document).ready(function() {
        $(".datatable").DataTable({
            "order": [[ 0, "asc" ]] // Sort by time by default
        });
    });
</script>
';
        include '../../includes/footer.php'; ?>