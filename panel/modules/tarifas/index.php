<?php
require_once '../../config/config.php';

$page_title = "Gestión de Tarifas";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Filtros
$local_id = isset($_GET['local']) ? (int)$_GET['local'] : 0;

// Obtener locales para filtro
try {
    $locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1'")->fetchAll();
} catch (PDOException $e) {
    $locales = [];
}

// Obtener tarifas
// Nota: 'local' es la columna FK en tbl_tarifa
$sql = "SELECT t.*, l.nombre as nombre_local 
        FROM tbl_tarifa t
        JOIN tbl_locales l ON t.local = l.id
        WHERE 1=1";
$params = [];

if ($local_id > 0) {
    $sql .= " AND t.local = ?";
    $params[] = $local_id;
}

$sql .= " ORDER BY l.nombre, t.nombre";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tarifas = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener tarifas: ' . $e->getMessage());
    $tarifas = [];
}

function formatDias($t)
{
    $dias = [];
    if ($t['l'] == '1') $dias[] = 'Lun';
    if ($t['m'] == '1') $dias[] = 'Mar';
    if ($t['x'] == '1') $dias[] = 'Mie';
    if ($t['j'] == '1') $dias[] = 'Jue';
    if ($t['v'] == '1') $dias[] = 'Vie';
    if ($t['s'] == '1') $dias[] = 'Sab';
    if ($t['d'] == '1') $dias[] = 'Dom';
    return empty($dias) ? 'Ninguno' : implode(', ', $dias);
}
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Tarifas</h1>
            <p class="page-subtitle">Gestiona los precios por Local</p>
        </div>
        <div>
            <a href="create.php<?php echo ($local_id > 0) ? '?local=' . $local_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Tarifa
            </a>
        </div>
    </div>

    <!-- Filtro de Local -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0; flex-grow: 1; max-width: 300px;">
                <label>Filtrar por Local:</label>
                <select name="local" class="form-control" onchange="this.form.submit()">
                    <option value="0">Todos los locales</option>
                    <?php foreach ($locales as $loc): ?>
                        <option value="<?php echo $loc['id']; ?>" <?php echo ($local_id == $loc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Local / Cine</th>
                    <th>Nombre de Tarifa</th>
                    <th>Precio</th>
                    <th>Días Válidos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tarifas as $tarifa): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tarifa['nombre_local']); ?></td>
                        <td><strong><?php echo htmlspecialchars($tarifa['nombre']); ?></strong></td>
                        <td>
                            <strong style="color: var(--cinerama-red); font-size: 1.1em;">
                                S/ <?php echo number_format($tarifa['precio'], 2); ?>
                            </strong>
                        </td>
                        <td><?php echo formatDias($tarifa); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $tarifa['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $tarifa['id']; ?>" class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo htmlspecialchars($tarifa['nombre']); ?>" title="Eliminar">
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