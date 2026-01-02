<?php
require_once '../../config/config.php';

$page_title = "Editar Programación";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$id = (int)$_GET['id'];

// Obtener datos existentes
try {
    $stmt = $db->prepare("SELECT * FROM tbl_cartelera WHERE id = ?");
    $stmt->execute([$id]);
    $cartelera = $stmt->fetch();

    if (!$cartelera) {
        redirect('index.php');
    }

    // Obtener datos para selectores
    $peliculas = $db->query("SELECT id, nombre FROM tbl_pelicula WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $salas = $db->query("SELECT id, nombre, local as local_id FROM tbl_sala WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $horas = $db->query("SELECT id, hora FROM tbl_hora ORDER BY hora")->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al cargar datos: ' . $e->getMessage());
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pelicula = (int)$_POST['pelicula'];
    $local = (int)$_POST['local'];
    $sala = !empty($_POST['sala']) ? (int)$_POST['sala'] : null;
    $fecha_inicio = sanitize($_POST['fecha_inicio']);
    $fecha_fin = sanitize($_POST['fecha_fin']);
    $formato = sanitize($_POST['formato']);
    $idioma = sanitize($_POST['idioma']);
    $estado = isset($_POST['estado']) ? '1' : '0';
    $id_hora_f1 = !empty($_POST['id_hora_f1']) ? $_POST['id_hora_f1'] : null;
    $id_hora_f2 = !empty($_POST['id_hora_f2']) ? $_POST['id_hora_f2'] : null;
    $id_hora_f3 = !empty($_POST['id_hora_f3']) ? $_POST['id_hora_f3'] : null;
    $id_hora_f4 = !empty($_POST['id_hora_f4']) ? $_POST['id_hora_f4'] : null;
    $id_hora_f5 = !empty($_POST['id_hora_f5']) ? $_POST['id_hora_f5'] : null;
    $id_hora_f6 = !empty($_POST['id_hora_f6']) ? $_POST['id_hora_f6'] : null;

    $horarios_str = implode(',', array_filter([$id_hora_f1, $id_hora_f2, $id_hora_f3, $id_hora_f4, $id_hora_f5, $id_hora_f6]));

    if (empty($pelicula) || empty($local) || empty($fecha_inicio) || empty($fecha_fin)) {
        showAlert('error', 'Error', 'Completa los campos obligatorios');
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE tbl_cartelera 
                SET pelicula=?, local=?, sala=?, fecha_inicio=?, fecha_fin=?, horarios=?, 
                    id_hora_f1=?, id_hora_f2=?, id_hora_f3=?, id_hora_f4=?, id_hora_f5=?, id_hora_f6=?,
                    formato=?, idioma=?, estado=?
                WHERE id=?
            ");
            $stmt->execute([
                $pelicula,
                $local,
                $sala,
                $fecha_inicio,
                $fecha_fin,
                $horarios_str,
                $id_hora_f1,
                $id_hora_f2,
                $id_hora_f3,
                $id_hora_f4,
                $id_hora_f5,
                $id_hora_f6,
                $formato,
                $idioma,
                $estado,
                $id
            ]);

            showAlert('success', 'Éxito', 'Programación actualizada correctamente');
            // Recargar
            $stmt = $db->prepare("SELECT * FROM tbl_cartelera WHERE id = ?");
            $stmt->execute([$id]);
            $cartelera = $stmt->fetch();
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al guardar: ' . $e->getMessage());
        }
    }
}

// Horarios seleccionados (array)
$horarios_array = !empty($cartelera['horarios']) ? explode(',', $cartelera['horarios']) : [];

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Programación</h1>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="">
            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="required">Película</label>
                    <select name="pelicula" class="form-control" required>
                        <?php foreach ($peliculas as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $cartelera['pelicula']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Cine (Local)</label>
                    <select name="local" id="select-local" class="form-control" required>
                        <?php foreach ($locales as $l): ?>
                            <option value="<?php echo $l['id']; ?>" <?php echo ($l['id'] == $cartelera['local']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($l['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Sala</label>
                    <select name="sala" id="select-sala" class="form-control">
                        <option value="">Seleccione Sala</option>
                        <?php foreach ($salas as $s): ?>
                            <option value="<?php echo $s['id']; ?>"
                                data-local="<?php echo $s['local_id']; ?>"
                                <?php echo ($s['id'] == $cartelera['sala']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Formato</label>
                    <select name="formato" class="form-control">
                        <option value="2D" <?php echo ($cartelera['formato'] == '2D') ? 'selected' : ''; ?>>2D</option>
                        <option value="3D" <?php echo ($cartelera['formato'] == '3D') ? 'selected' : ''; ?>>3D</option>
                        <option value="IMAX" <?php echo ($cartelera['formato'] == 'IMAX') ? 'selected' : ''; ?>>IMAX</option>
                        <option value="XD" <?php echo ($cartelera['formato'] == 'XD') ? 'selected' : ''; ?>>XD</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Idioma</label>
                    <select name="idioma" class="form-control">
                        <option value="DOB" <?php echo ($cartelera['idioma'] == 'DOB') ? 'selected' : ''; ?>>Doblada (ESP)</option>
                        <option value="SUB" <?php echo ($cartelera['idioma'] == 'SUB') ? 'selected' : ''; ?>>Subtitulada (SUB)</option>
                        <option value="ORIG" <?php echo ($cartelera['idioma'] == 'ORIG') ? 'selected' : ''; ?>>Idioma Original</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required value="<?php echo $cartelera['fecha_inicio']; ?>">
                </div>

                <div class="form-group">
                    <label class="required">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" required value="<?php echo $cartelera['fecha_fin']; ?>">
                </div>
            </div>

            <hr style="margin: 30px 0;">

            <hr style="margin: 30px 0;">

            <h5 class="mb-3">Horarios de Función (F1 - F6)</h5>
            <div class="row">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Función <?php echo $i; ?> (F<?php echo $i; ?>)</label>
                        <select name="id_hora_f<?php echo $i; ?>" class="form-control">
                            <option value="">-- Seleccionar hora --</option>
                            <?php foreach ($horas as $hora): ?>
                                <option value="<?php echo $hora['id']; ?>"
                                    <?php echo ($cartelera["id_hora_f$i"] == $hora['id']) ? 'selected' : ''; ?>>
                                    <?php echo date('g:i A', strtotime($hora['hora'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo ($cartelera['estado'] == '1') ? 'checked' : ''; ?>>
                    Activo
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const localSelect = document.getElementById('select-local');
        const salaSelect = document.getElementById('select-sala');
        const allSalas = Array.from(salaSelect.options);
        const selectedSala = "<?php echo $cartelera['sala']; ?>";

        function filterSalas() {
            const localId = localSelect.value;
            const currentSala = salaSelect.value || selectedSala;
            salaSelect.innerHTML = '';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.text = 'Seleccione Sala';
            salaSelect.appendChild(defaultOption);

            allSalas.forEach(option => {
                if (option.value === '' || option.dataset.local === localId) {
                    if (option.value !== '') {
                        salaSelect.appendChild(option);
                        if (option.value == currentSala) {
                            option.selected = true;
                        }
                    }
                }
            });
        }

        localSelect.addEventListener('change', filterSalas);
        filterSalas();
    });
</script>

<?php include '../../includes/footer.php'; ?>