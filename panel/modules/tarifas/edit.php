<?php
require_once '../../config/config.php';

$page_title = "Editar Tarifa";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('index.php');

// Obtener tarifa
try {
    $stmt = $db->prepare("SELECT * FROM tbl_tarifa WHERE id = ?");
    $stmt->execute([$id]);
    $tarifa = $stmt->fetch();
    if (!$tarifa) redirect('index.php');
} catch (PDOException $e) {
    redirect('index.php');
}

// Obtener locales
$locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_local = (int)$_POST['id_local'];
    $nombre = sanitize($_POST['nombre']);
    $precio = (float)$_POST['precio'];

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
            $stmt = $db->prepare("UPDATE tbl_tarifa SET local=?, nombre=?, precio=?, l=?, m=?, x=?, j=?, v=?, s=?, d=? WHERE id=?");
            $stmt->execute([$id_local, $nombre, $precio, $l, $m, $x, $j, $v, $s, $d, $id]);

            showAlert('success', 'Éxito', 'Tarifa actualizada');
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
            <h1 class="page-title">Editar Tarifa</h1>
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
                    <?php foreach ($locales as $loc): ?>
                        <option value="<?php echo $loc['id']; ?>" <?php echo ($tarifa['local'] == $loc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required">Nombre de la Tarifa</label>
                <input type="text" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($tarifa['nombre']); ?>">
            </div>

            <div class="form-group">
                <label class="required">Precio (S/)</label>
                <input type="number" name="precio" class="form-control" step="0.50" min="0" required value="<?php echo $tarifa['precio']; ?>">
            </div>

            <div class="form-group">
                <label>Días Válidos</label>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                    <label><input type="checkbox" name="dias[]" value="l" <?php echo $tarifa['l'] == '1' ? 'checked' : ''; ?>> Lun</label>
                    <label><input type="checkbox" name="dias[]" value="m" <?php echo $tarifa['m'] == '1' ? 'checked' : ''; ?>> Mar</label>
                    <label><input type="checkbox" name="dias[]" value="x" <?php echo $tarifa['x'] == '1' ? 'checked' : ''; ?>> Mie</label>
                    <label><input type="checkbox" name="dias[]" value="j" <?php echo $tarifa['j'] == '1' ? 'checked' : ''; ?>> Jue</label>
                    <label><input type="checkbox" name="dias[]" value="v" <?php echo $tarifa['v'] == '1' ? 'checked' : ''; ?>> Vie</label>
                    <label><input type="checkbox" name="dias[]" value="s" <?php echo $tarifa['s'] == '1' ? 'checked' : ''; ?>> Sab</label>
                    <label><input type="checkbox" name="dias[]" value="d" <?php echo $tarifa['d'] == '1' ? 'checked' : ''; ?>> Dom</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Tarifa</button>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>