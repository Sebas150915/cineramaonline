<?php
require_once '../../config/config.php';

$page_title = "Nueva Sala";

// Obtener local si viene por parámetro
$local_id = isset($_GET['local']) ? (int)$_GET['local'] : 0;

// Obtener locales para el select
try {
    $stmt = $db->query("SELECT id, nombre FROM tbl_locales WHERE estado = '1' ORDER BY nombre");
    $locales = $stmt->fetchAll();
} catch (PDOException $e) {
    $locales = [];
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize($_POST['nombre']);
    $local = (int)$_POST['local'];
    $capacidad = (int)$_POST['capacidad'];
    $filas = sanitize($_POST['filas']);
    $columnas = sanitize($_POST['columnas']);
    $estado = isset($_POST['estado']) ? '1' : '0';

    // Validar
    if (empty($nombre) || $local <= 0 || $capacidad <= 0) {
        showAlert('error', 'Error', 'Todos los campos obligatorios deben ser completados');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO tbl_sala (nombre, local, capacidad, filas, columnas, estado) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $local, $capacidad, $filas, $columnas, $estado]);

            $sala_id = $db->lastInsertId();

            showAlert('success', 'Éxito', 'Sala creada correctamente');
            redirect('asientos.php?id=' . $sala_id);
        } catch (PDOException $e) {
            showAlert('error', 'Error', 'Error al crear la sala: ' . $e->getMessage());
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
            <h1 class="page-title">Nueva Sala</h1>
            <p class="page-subtitle">Agrega una nueva sala de cine</p>
        </div>
        <div>
            <a href="index.php<?php echo ($local_id > 0) ? '?local=' . $local_id : ''; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Nombre de la Sala</label>
                    <input type="text" name="nombre" class="form-control" required
                        placeholder="Ej: SALA 01, SALA VIP, SALA 3D..."
                        value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="required">Local/Cine</label>
                    <select name="local" class="form-control" required>
                        <option value="">Seleccionar local...</option>
                        <?php foreach ($locales as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>"
                                <?php echo (($local_id > 0 && $local_id == $loc['id']) || (isset($_POST['local']) && $_POST['local'] == $loc['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div class="form-group">
                    <label class="required">Capacidad Total</label>
                    <input type="number" name="capacidad" class="form-control" required min="1"
                        placeholder="Número total de asientos"
                        value="<?php echo isset($_POST['capacidad']) ? $_POST['capacidad'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Número de Filas</label>
                    <input type="number" name="filas" class="form-control" min="1" max="26"
                        placeholder="Ej: 10"
                        value="<?php echo isset($_POST['filas']) ? $_POST['filas'] : ''; ?>">
                    <small class="form-help">Máximo 26 filas (A-Z)</small>
                </div>

                <div class="form-group">
                    <label>Asientos por Fila</label>
                    <input type="number" name="columnas" class="form-control" min="1"
                        placeholder="Ej: 15"
                        value="<?php echo isset($_POST['columnas']) ? $_POST['columnas'] : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="estado" value="1" checked>
                    Activo
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Sala y Configurar Asientos
                </button>
                <a href="index.php<?php echo ($local_id > 0) ? '?local=' . $local_id : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>