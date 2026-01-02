<?php
require_once '../../config/config.php';

$page_title = "Nueva Programación";

// Obtener datos para los selectores
try {
    $peliculas = $db->query("SELECT id, nombre FROM tbl_pelicula WHERE estado = '1' ORDER BY nombre")->fetchAll();
    $locales = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1' ORDER BY nombre")->fetchAll();

    // Obtener todas las salas para filtrarlas con JS
    $salas = $db->query("SELECT id, nombre, local as local_id FROM tbl_sala WHERE estado = '1' ORDER BY nombre")->fetchAll();

    // Obtener horas
    $horas = $db->query("SELECT id, hora FROM tbl_hora ORDER BY hora")->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al cargar datos: ' . $e->getMessage());
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error de seguridad (CSRF). Por favor recargue la página.');
    } else {
        $pelicula = (int)$_POST['pelicula'];
        $local = (int)$_POST['local'];
        $sala = !empty($_POST['sala']) ? (int)$_POST['sala'] : null;
        $fecha_inicio = sanitize($_POST['fecha_inicio']);
        $fecha_fin = sanitize($_POST['fecha_fin']);
        $formato = sanitize($_POST['formato']);
        $idioma = sanitize($_POST['idioma']);
        $estado = isset($_POST['estado']) ? '1' : '0';

        // Recoger horarios individuales
        $id_hora_f1 = !empty($_POST['id_hora_f1']) ? $_POST['id_hora_f1'] : null;
        $id_hora_f2 = !empty($_POST['id_hora_f2']) ? $_POST['id_hora_f2'] : null;
        $id_hora_f3 = !empty($_POST['id_hora_f3']) ? $_POST['id_hora_f3'] : null;
        $id_hora_f4 = !empty($_POST['id_hora_f4']) ? $_POST['id_hora_f4'] : null;
        $id_hora_f5 = !empty($_POST['id_hora_f5']) ? $_POST['id_hora_f5'] : null;
        $id_hora_f6 = !empty($_POST['id_hora_f6']) ? $_POST['id_hora_f6'] : null;

        // Horarios string (legacy/auxiliar)
        $horarios_str = implode(',', array_filter([$id_hora_f1, $id_hora_f2, $id_hora_f3, $id_hora_f4, $id_hora_f5, $id_hora_f6]));

        if (empty($pelicula) || empty($local) || empty($fecha_inicio) || empty($fecha_fin)) {
            showAlert('error', 'Error', 'Completa los campos obligatorios');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO tbl_cartelera 
                    (pelicula, local, sala, fecha_inicio, fecha_fin, horarios, id_hora_f1, id_hora_f2, id_hora_f3, id_hora_f4, id_hora_f5, id_hora_f6, formato, idioma, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                    $estado
                ]);

                showAlert('success', 'Éxito', 'Programación creada correctamente');
                redirect('index.php');
            } catch (PDOException $e) {
                // Log error instead of showing detailed message
                error_log($e->getMessage());
                showAlert('error', 'Error', 'Ocurrió un error al guardar la programación.');
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nueva Programación</h1>
            <p class="page-subtitle">Programar película en cine</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Película -->
                <div class="form-group">
                    <label class="required">Película</label>
                    <select name="pelicula" class="form-control" required>
                        <option value="">Seleccione Película</option>
                        <?php foreach ($peliculas as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Local (Cine) -->
                <div class="form-group">
                    <label class="required">Cine (Local)</label>
                    <select name="local" id="select-local" class="form-control" required>
                        <option value="">Seleccione Cine</option>
                        <?php foreach ($locales as $l): ?>
                            <option value="<?php echo $l['id']; ?>">
                                <?php echo htmlspecialchars($l['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sala (Filtrada por JS) -->
                <div class="form-group">
                    <label>Sala</label>
                    <select name="sala" id="select-sala" class="form-control">
                        <option value="">Seleccione Sala</option>
                        <?php foreach ($salas as $s): ?>
                            <option value="<?php echo $s['id']; ?>" data-local="<?php echo $s['local_id']; ?>">
                                <?php echo htmlspecialchars($s['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-help">Selecciona un cine primero</small>
                </div>

                <!-- Formato e Idioma -->
                <div class="form-group">
                    <label>Formato</label>
                    <select name="formato" class="form-control">
                        <option value="2D">2D</option>
                        <option value="3D">3D</option>
                        <option value="IMAX">IMAX</option>
                        <option value="XD">XD</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Idioma</label>
                    <select name="idioma" class="form-control">
                        <option value="DOB">Doblada (ESP)</option>
                        <option value="SUB">Subtitulada (SUB)</option>
                        <option value="ORIG">Idioma Original</option>
                    </select>
                </div>

                <!-- Fechas -->
                <div class="form-group">
                    <label class="required">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="required">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
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
                                <option value="<?php echo $hora['id']; ?>">
                                    <?php echo date('g:i A', strtotime($hora['hora'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endfor; ?>
            </div>
            <small class="form-help text-muted">Selecciona la hora específica para cada columna del reporte.</small>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo (Publicado)
                </label>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Programación
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

        function filterSalas() {
            const localId = localSelect.value;
            salaSelect.innerHTML = '';

            // Agregar opción por defecto
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.text = 'Seleccione Sala';
            salaSelect.appendChild(defaultOption);

            // Filtrar y agregar salas
            allSalas.forEach(option => {
                if (option.value === '' || option.dataset.local === localId) {
                    if (option.value !== '') { // Evitar duplicar la opción por defecto
                        salaSelect.appendChild(option);
                    }
                }
            });
        }

        localSelect.addEventListener('change', filterSalas);
        // Ejecutar al inicio por si hay valor seleccionado
        filterSalas();
    });
</script>

<?php include '../../includes/footer.php'; ?>