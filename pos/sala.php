<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

$id_funcion = $_GET['id_funcion'] ?? 0;

if (!$id_funcion) {
    header("Location: dashboard.php");
    exit;
}

// Get Data
$stmt = $db->prepare("
    SELECT f.*, p.nombre as pelicula, s.nombre as sala, s.filas, s.columnas, s.id as id_sala, l.nombre as cine, h.hora as hora_inicio
    FROM tbl_funciones f
    JOIN tbl_pelicula p ON f.id_pelicula = p.id
    JOIN tbl_sala s ON f.id_sala = s.id
    JOIN tbl_locales l ON s.local = l.id
    JOIN tbl_hora h ON f.id_hora = h.id
    WHERE f.id = ? AND f.estado = '1'
");
$stmt->execute([$id_funcion]);
$funcion = $stmt->fetch();

if (!$funcion) {
    die("Función no encontrada");
}

// Get Seats
$stmtAsientos = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
$stmtAsientos->execute([$funcion['id_sala']]);
$asientosBase = $stmtAsientos->fetchAll();

// Get Occupied Seats (Paid or Pending from other POS?)
// For now, consistent with frontend: only confirm PAGADO are occupied.
// Might want to add 'PROCESANDO' state later to lock seats.
$stmtOcupados = $db->prepare("
    SELECT b.id_asiento 
    FROM tbl_boletos b
    JOIN tbl_ventas v ON b.id_venta = v.id
    WHERE v.id_funcion = ? 
    AND v.estado IN ('PAGADO', 'PENDIENTE')
    AND b.estado = 'ACTIVO'
");
$stmtOcupados->execute([$id_funcion]);
$ocupadosRaw = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);
$ocupados = array_flip($ocupadosRaw);

include 'includes/header.php';
?>

<div class="sala-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h2 style="margin: 0;"><?php echo htmlspecialchars($funcion['pelicula']); ?></h2>
        <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($funcion['cine'] . ' - ' . $funcion['sala']); ?> | <?php echo date('d/m H:i', strtotime($funcion['fecha'] . ' ' . $funcion['hora_inicio'])); ?></p>
    </div>
    <a href="funciones.php?id=<?php echo $funcion['id_pelicula']; ?>" class="btn btn-secondary">Cambiar Función</a>
</div>

<div class="screen-container" style="perspective: 300px; margin-bottom: 30px;">
    <div class="screen" style="background: #333; color: white; text-align: center; padding: 5px; transform: rotateX(-5deg); width: 80%; margin: 0 auto; border-radius: 4px;">PANTALLA</div>
</div>

<form action="procesar.php" method="POST" id="form-sala">
    <input type="hidden" name="id_funcion" value="<?php echo $id_funcion; ?>">
    <input type="hidden" name="asientos" id="input_asientos">

    <div class="seats-grid" style="display: grid; grid-template-columns: repeat(<?php echo $funcion['columnas']; ?>, 35px); gap: 5px; justify-content: center;">
        <?php foreach ($asientosBase as $asiento): ?>
            <?php
            $id = $asiento['id'];
            $rotulo = $asiento['fila'] . $asiento['num_asiento'];
            $tipo = $asiento['tipo'];
            $ocupado = isset($ocupados[$id]);

            $bg = '#444'; // Normal
            $cursor = 'pointer';
            if ($tipo == 'PASILLO') {
                $bg = 'transparent';
                $cursor = 'default';
            }
            if ($ocupado) {
                $bg = '#cc0000';
                $cursor = 'not-allowed';
            }

            if ($tipo != 'PASILLO'):
            ?>
                <div class="seat <?php echo $ocupado ? 'occupied' : ''; ?>"
                    data-id="<?php echo $id; ?>"
                    data-rotulo="<?php echo $rotulo; ?>"
                    style="width: 35px; height: 35px; background: <?php echo $bg; ?>; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; cursor: <?php echo $cursor; ?>;"
                    onclick="toggleSeat(this)">
                    <?php echo $rotulo; ?>
                </div>
            <?php else: ?>
                <div style="width: 35px; height: 35px;"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px; text-align: right;">
        <span id="seat-count">0</span> Asientos seleccionados
        <button type="submit" class="btn-continue" style="background: #c01820; color: white; border: none; padding: 10px 30px; font-size: 1.1rem; border-radius: 4px; margin-left: 20px;" disabled>PROCESAR PAGO <i class="fas fa-chevron-right"></i></button>
    </div>
</form>

<script>
    const selected = new Set();
    const input = document.getElementById('input_asientos');
    const btn = document.querySelector('.btn-continue');
    const label = document.getElementById('seat-count');

    function toggleSeat(el) {
        if (el.classList.contains('occupied')) return;

        const id = el.dataset.id;

        if (selected.has(id)) {
            selected.delete(id);
            el.style.background = '#444';
            el.style.border = 'none';
        } else {
            selected.add(id);
            el.style.background = '#28a745';
        }

        updateUI();
    }

    function updateUI() {
        label.innerText = selected.size;
        input.value = Array.from(selected).join(',');
        btn.disabled = selected.size === 0;
    }
</script>

<?php include 'includes/footer.php'; ?>