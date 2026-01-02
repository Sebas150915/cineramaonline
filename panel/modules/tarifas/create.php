<?php
require_once '../../config/config.php';

$page_title = "Nueva Tarifa";

$local_default = isset($_GET['local']) ? (int)$_GET['local'] : 0;

// Obtener locales
try {
    $locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1'")->fetchAll();
} catch (PDOException $e) {
    $locales = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_local = (int)$_POST['id_local'];
    $nombre = sanitize($_POST['nombre']);
    $precio = (float)$_POST['precio'];

    // Mapeo de días a columnas
    $dias_select = isset($_POST['dias']) ? $_POST['dias'] : [];
    $l = in_array('l', $dias_select) ? '1' : '0';
    $m = in_array('m', $dias_select) ? '1' : '0';
    $x = in_array('x', $dias_select) ? '1' : '0';
    $j = in_array('j', $dias_select) ? '1' : '0';
    $v = in_array('v', $dias_select) ? '1' : '0';
    $s = in_array('s', $dias_select) ? '1' : '0';
    $d = in_array('d', $dias_select) ? '1' : '0';

    if ($id_local <= 0 || empty($nombre) || $precio < 0) {
        showAlert('error', 'Error', 'Campos obligatorios incompletos');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tbl_tarifa (local, nombre, precio, l, m, x, j, v, s, d) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_local, $nombre, $precio, $l, $m, $x, $j, $v, $s, $d]);

            showAlert('success', 'Éxito', 'Tarifa creada correctamente');
            redirect('index.php?local=' . $id_local);
        } catch (PDOException $e) {
            showAlert('error', 'Error', $e->getMessage());
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nueva Tarifa</h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label class="required">Local / Cine</label>
                <select name="id_local" class="form-control" required>
                    <option value="">Seleccionar Local...</option>
                    <?php foreach ($locales as $loc): ?>
                        <option value="<?php echo $loc['id']; ?>" <?php echo ($local_default == $loc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required">Nombre de la Tarifa</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Ej: General, Niños, Martes Loco...">
            </div>

            <div class="form-group">
                <label class="required">Precio (S/)</label>
                <input type="number" name="precio" class="form-control" step="0.50" min="0" required>
            </div>

            <div class="form-group">
                <label>Días Válidos</label>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                    <label><input type="checkbox" name="dias[]" value="l" checked> Lun</label>
                    <label><input type="checkbox" name="dias[]" value="m" checked> Mar</label>
                    <label><input type="checkbox" name="dias[]" value="x" checked> Mie</label>
                    <label><input type="checkbox" name="dias[]" value="j" checked> Jue</label>
                    <label><input type="checkbox" name="dias[]" value="v" checked> Vie</label>
                    <label><input type="checkbox" name="dias[]" value="s" checked> Sab</label>
                    <label><input type="checkbox" name="dias[]" value="d" checked> Dom</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Tarifa</button>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>