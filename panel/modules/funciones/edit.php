<?php
require_once '../../config/config.php';

$page_title = "Editar Función";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    showAlert('error', 'Error', 'ID inválido');
    redirect('index.php');
}

// Obtener datos actuales
try {
    $stmt = $db->prepare("SELECT * FROM tbl_funciones WHERE id = ?");
    $stmt->execute([$id]);
    $funcion = $stmt->fetch();

    if (!$funcion) {
        showAlert('error', 'Error', 'Función no encontrada');
        redirect('index.php');
    }

    // Listas para selects
    $peliculas = $db->query("SELECT id, nombre FROM tbl_pelicula WHERE estado = '1' OR id = {$funcion['id_pelicula']} ORDER BY nombre")->fetchAll();
    $salas = $db->query("
        SELECT s.id, s.nombre, l.nombre as local 
        FROM tbl_sala s 
        JOIN tbl_locales l ON s.local = l.id 
        WHERE s.estado = '1' OR s.id = {$funcion['id_sala']}
        ORDER BY l.nombre, s.nombre
    ")->fetchAll();
    $horarios = $db->query("SELECT id, hora FROM tbl_hora ORDER BY hora")->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', $e->getMessage());
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pelicula = (int)$_POST['id_pelicula'];
    $id_sala = (int)$_POST['id_sala'];
    $id_hora = (int)$_POST['id_hora'];
    $fecha = sanitize($_POST['fecha']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    if ($id_pelicula <= 0 || $id_sala <= 0 || $id_hora <= 0 || empty($fecha)) {
        showAlert('error', 'Error', 'Todos los campos son obligatorios');
    } else {
        try {
            // Validar duplicados (con excepción del actual) (Misma sala, fecha y hora)
            $check = $db->prepare("SELECT COUNT(*) FROM tbl_funciones WHERE id_sala = ? AND fecha = ? AND id_hora = ? AND id != ?");
            $check->execute([$id_sala, $fecha, $id_hora, $id]);

            if ($check->fetchColumn() > 0) {
                showAlert('warning', 'Conflicto', 'Ya existe un función en esa sala a esa hora');
            } else {
                $stmt = $db->prepare("
                    UPDATE tbl_funciones 
                    SET id_pelicula = ?, id_sala = ?, id_hora = ?, fecha = ?, estado = ?
                    WHERE id = ?
                ");
                $stmt->execute([$id_pelicula, $id_sala, $id_hora, $fecha, $estado, $id]);

                showAlert('success', 'Éxito', 'Función actualizada correctamente');
                redirect('index.php?fecha=' . $fecha);
            }
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
            <h1 class="page-title">Editar Función</h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    <div class="card">
        <form method="POST">
            <div class="form-group">
                <label class="required">Película</label>
                <select name="id_pelicula" class="form-control select2" required>
                    <?php foreach ($peliculas as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $funcion['id_pelicula']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required">Sala</label>
                <select name="id_sala" class="form-control select2" required>
                    <?php foreach ($salas as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $funcion['id_sala']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['local'] . ' - ' . $s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Fecha</label>
                    <input type="date" name="fecha" class="form-control" required
                        value="<?php echo $funcion['fecha']; ?>">
                </div>

                <div class="form-group">
                    <label class="required">Horario</label>
                    <select name="id_hora" class="form-control" required>
                        <?php foreach ($horarios as $h): ?>
                            <option value="<?php echo $h['id']; ?>" <?php echo ($h['id'] == $funcion['id_hora']) ? 'selected' : ''; ?>>
                                <?php echo date('h:i A', strtotime($h['hora'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($funcion['estado'] == '1') ? 'checked' : ''; ?>> Activo
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Función</button>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2();
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>