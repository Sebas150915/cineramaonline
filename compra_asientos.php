<?php
require_once 'includes/front_config.php';

$id_funcion = isset($_GET['id_funcion']) ? (int)$_GET['id_funcion'] : 0;

if ($id_funcion <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos de la función
try {
    $stmt = $db->prepare("
        SELECT f.*, p.nombre as pelicula, s.nombre as sala, s.filas, s.columnas, s.id as id_sala, l.nombre as cine
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        WHERE f.id = ? AND f.estado = '1'
    ");
    $stmt->execute([$id_funcion]);
    $funcion = $stmt->fetch();

    if (!$funcion) {
        die("Función no válida");
    }

    // Obtener asientos de la sala (estructura base)
    $stmtAsientos = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
    $stmtAsientos->execute([$funcion['id_sala']]);
    $asientosBase = $stmtAsientos->fetchAll();

    // Obtener asientos OCUPADOS para esta función (en tbl_boletos de ventas activas/pagadas)
    // Nota: 'ANULADO' no cuenta como ocupado.
    $stmtOcupados = $db->prepare("
        SELECT b.id_asiento 
        FROM tbl_boletos b
        JOIN tbl_ventas v ON b.id_venta = v.id
        WHERE v.id_funcion = ? 
        AND v.estado = 'PAGADO'
        AND b.estado = 'ACTIVO'
    ");
    $stmtOcupados->execute([$id_funcion]);
    $ocupadosRaw = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);
    $ocupados = array_flip($ocupadosRaw); // Para búsqueda rápida

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = "Selección de Asientos";
include 'includes/header_front.php';
?>

<div class="container">
    <h2 class="section-title">Elige tus Butacas</h2>
    <p style="text-align: center; color: #aaa;">
        <?php echo htmlspecialchars($funcion['cine'] . ' - ' . $funcion['sala']); ?> |
        <strong><?php echo htmlspecialchars($funcion['pelicula']); ?></strong>
    </p>

    <div style="text-align: center; margin: 20px 0;">
        <span style="font-size: 1.5rem; font-weight: bold; color: #e50914;">Tiempo restante: <span id="timer-display">05:00</span></span>
    </div>

    <div class="screen-container">
        <div class="screen">PANTALLA</div>
    </div>

    <form method="POST" action="compra_pre_booking.php" id="form-asientos">
        <input type="hidden" name="id_funcion" value="<?php echo $id_funcion; ?>">

        <?php
        // Extract unique rows to build map helpers
        // We need to map Row Letter -> Row Index (1-based)
        $unique_rows = [];
        foreach ($asientosBase as $a) {
            if (!in_array($a['fila'], $unique_rows)) {
                $unique_rows[] = $a['fila'];
            }
        }
        // Assuming rows are sorted (A, B, C...)
        // Map 'A' -> 0, 'B' -> 1 so we can use index for grid
        // We can't rely on 'A' being 0 if it starts at F, so we re-index.
        $unique_rows = array_values($unique_rows); // Reset keys logic
        $rowMap = array_flip($unique_rows); // 'A' => 0, 'B' => 1

        // Grid Dimensions
        // Cols = 1 (Label) + $funcion['columnas'] + 1 (Label)
        // But wait, if columna in DB is e.g. 20, we need 20 columns max.

        $dbColumns = (int)$funcion['columnas'];
        if ($dbColumns <= 0) $dbColumns = 1; // Safety fallback

        // Grid Definition:
        // Col 1: Label Left (30px)
        // Cols 2..N+1: Seats (35px each)
        // Col N+2: Label Right (30px)
        ?>

        <div class="seats-container" style="display: grid; 
                 grid-template-columns: 30px repeat(<?php echo $dbColumns; ?>, 35px) 30px; 
                 gap: 5px; margin: 30px auto; width: max-content;">

            <!-- Render Row Labels (Left and Right) -->
            <?php foreach ($unique_rows as $index => $letter): ?>
                <?php $rowIndex = $index + 1; // 1-based row index for Grid 
                ?>

                <!-- Left Label -->
                <div style="grid-column: 1; grid-row: <?php echo $rowIndex; ?>; 
                                font-weight: bold; color: #333; display: flex; align-items: center; justify-content: center;">
                    <?php echo $letter; ?>
                </div>

                <!-- Right Label -->
                <div style="grid-column: <?php echo $dbColumns + 2; ?>; grid-row: <?php echo $rowIndex; ?>; 
                                font-weight: bold; color: #333; display: flex; align-items: center; justify-content: center;">
                    <?php echo $letter; ?>
                </div>
            <?php endforeach; ?>

            <!-- Render Seats -->
            <?php foreach ($asientosBase as $asiento): ?>
                <?php
                $id = $asiento['id'];
                $rotulo = $asiento['fila'] . $asiento['num_asiento'];
                $tipo = $asiento['tipo'];
                $isOcupado = isset($ocupados[$id]);
                $rowLetter = $asiento['fila'];
                $colNumber = (int)$asiento['columna']; // DB column index 1-based

                $class = 'seat ' . strtolower($tipo);
                if ($isOcupado) $class .= ' occupied';
                if ($tipo == 'PASILLO') $class = 'aisle';

                // Determine Grid Position
                if (isset($rowMap[$rowLetter])) {
                    $rIndex = $rowMap[$rowLetter] + 1; // 1-based row
                    $cIndex = $colNumber + 1; // +1 offset for Left Label

                    if ($tipo == 'PASILLO') {
                        // Render nothing or an aisle div if strict spacing needed
                        // <div class="aisle" style="grid-column: ..."></div>
                    } else {
                ?>
                        <div class="<?php echo $class; ?>"
                            style="grid-column: <?php echo $cIndex; ?>; grid-row: <?php echo $rIndex; ?>;"
                            data-id="<?php echo $id; ?>"
                            data-rotulo="<?php echo htmlspecialchars($rotulo); ?>"
                            onclick="toggleSeat(this)">
                            <?php echo $rotulo; ?>
                        </div>
                <?php
                    }
                }
                ?>
            <?php endforeach; ?>
        </div>

        <!-- Input hidden para enviar los IDs seleccionados -->
        <input type="hidden" name="selected_seats" id="selected_seats">

        <div style="text-align: center; margin-top: 30px;">
            <div style="margin-bottom: 20px; color: #333; font-weight: bold;">
                <span style="margin-right: 15px;"><span class="seat example"></span> Libre</span>
                <span style="margin-right: 15px;"><span class="seat example selected"></span> Seleccionado</span>
                <span><span class="seat example occupied"></span> Ocupado</span>
            </div>

            <button type="submit" class="btn" id="btn-continuar" disabled>Continuar a la Compra</button>
        </div>
    </form>
</div>

<style>
    .screen {
        width: 80%;
        height: 40px;
        background: #333;
        margin: 0 auto 30px;
        border-radius: 5px;
        text-align: center;
        line-height: 40px;
        color: #666;
        box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
        transform: perspective(300px) rotateX(-5deg);
    }

    .seat {
        width: 35px;
        height: 35px;
        background: #444;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #fff;
        user-select: none;
        transition: background .2s;
    }

    .seat.normal:hover {
        background: #666;
    }

    /* Selected state */
    .seat.selected {
        background: #4CAF50 !important;
        /* Green */
        color: #fff;
    }

    /* Occupied state */
    .seat.occupied {
        background: #b71c1c !important;
        /* Strong red */
        color: #fff !important;
        cursor: not-allowed;
    }

    /* Aisle (if rendered) */
    .seat.aisle {
        background: transparent;
        cursor: default;
        pointer-events: none;
    }

    /* Example legend icons override */
    .seat.example {
        width: 20px;
        height: 20px;
        display: inline-block;
        vertical-align: middle;
        margin-right: 5px;
        font-size: 0;
    }
</style>

<script>
    const MAX_SEATS = 5;
    const TIME_LIMIT = 5 * 60; // 5 minutes in seconds

    let selectedCount = 0;
    let timeRemaining = TIME_LIMIT;

    function toggleSeat(element) {
        if (element.classList.contains('occupied')) return;
        if (element.classList.contains('aisle')) return;

        if (element.classList.contains('selected')) {
            // Deselect
            element.classList.remove('selected');
            selectedCount--;
        } else {
            // Select
            if (selectedCount >= MAX_SEATS) {
                alert('Solo puedes seleccionar un m\u00e1ximo de ' + MAX_SEATS + ' butacas.');
                return;
            }
            element.classList.add('selected');
            selectedCount++;
        }

        updateForm();
    }

    function updateForm() {
        const selected = document.querySelectorAll('.seat.selected:not(.example)');
        const ids = Array.from(selected).map(el => el.getAttribute('data-id'));
        document.getElementById('selected_seats').value = ids.join(',');

        const btn = document.getElementById('btn-continuar');
        btn.disabled = (ids.length === 0);
    }

    // Timer Logic
    function startTimer() {
        const display = document.getElementById('timer-display');

        const interval = setInterval(() => {
            let minutes = Math.floor(timeRemaining / 60);
            let seconds = timeRemaining % 60;

            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;

            display.textContent = minutes + ':' + seconds;

            if (timeRemaining <= 0) {
                clearInterval(interval);
                alert("El tiempo de selecci\u00f3n ha expirado. La p\u00e1gina se recargar\u00e1.");
                location.reload();
            }

            timeRemaining--;
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        startTimer();
    });
</script>

<?php include 'includes/footer_front.php'; ?>