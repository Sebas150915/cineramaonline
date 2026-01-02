<?php
require_once '../panel/config/config.php';

$cartelera_id = isset($_GET['cartelera_id']) ? (int)$_GET['cartelera_id'] : 0;
$hora_id = isset($_GET['hora_id']) ? (int)$_GET['hora_id'] : 0;
// $num_funcion = isset($_GET['num_funcion']) ? (int)$_GET['num_funcion'] : 0; // Solo referencial
$fecha_actual = date('Y-m-d'); // Kiosko siempre es hoy

if (!$cartelera_id || !$hora_id) {
    header('Location: index.php');
    exit;
}

try {
    // 1. Obtener datos Cartelera + Sala + Pelicula + Hora
    $stmt = $db->prepare("
        SELECT c.*, p.nombre as pelicula_nombre, p.img as pelicula_imagen,
               s.id as sala_id, s.nombre as sala_nombre, l.nombre as local_nombre,
               h.hora as hora_valor
        FROM tbl_cartelera c
        JOIN tbl_pelicula p ON c.pelicula = p.id
        JOIN tbl_sala s ON c.sala = s.id
        JOIN tbl_locales l ON c.local = l.id
        JOIN tbl_hora h ON h.id = ?
        WHERE c.id = ?
    ");
    $stmt->execute([$hora_id, $cartelera_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        die("Información no válida.");
    }

    $sala_id = $info['sala_id'];

    // 2. Buscar Función Real (tbl_funciones)
    $stmtFunc = $db->prepare("
        SELECT id FROM tbl_funciones 
        WHERE id_sala = ? AND id_hora = ? AND fecha = ?
    ");
    $stmtFunc->execute([$sala_id, $hora_id, $fecha_actual]);
    $funcion_real = $stmtFunc->fetch(PDO::FETCH_ASSOC);
    $id_funcion = $funcion_real ? $funcion_real['id'] : null;

    // 3. Auto-recuperación: Si no existe, crearla
    if (!$id_funcion) {
        $stmtCreate = $db->prepare("
            INSERT INTO tbl_funciones (id_pelicula, id_sala, id_hora, fecha, estado) 
            VALUES (?, ?, ?, ?, '1')
        ");
        $stmtCreate->execute([$info['pelicula'], $sala_id, $hora_id, $fecha_actual]);
        $id_funcion = $db->lastInsertId();
    }

    // 4. Obtener estado de asientos (Ocupados)
    // Buscamos en tbl_boletos unidos a ventas pendientes/pagadas
    $ocupados = [];
    if ($id_funcion) {
        $stmtOcup = $db->prepare("
            SELECT b.id_asiento 
            FROM tbl_boletos b
            JOIN tbl_ventas v ON b.id_venta = v.id
            WHERE v.id_funcion = ? 
            AND v.estado IN ('PAGADO', 'PENDIENTE')
            AND b.estado = 'ACTIVO'
        ");
        $stmtOcup->execute([$id_funcion]);
        $ocupados = $stmtOcup->fetchAll(PDO::FETCH_COLUMN); // Array of Layout Seat IDs
    }

    // 5. Obtener Mapa de la Sala
    $stmtMap = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
    $stmtMap->execute([$sala_id]);
    $asientos_db = $stmtMap->fetchAll(PDO::FETCH_ASSOC);

    // Organizar para la vista
    $asientos_map = [];
    $filas = [];
    $max_col = 0;

    foreach ($asientos_db as $as) {
        $asientos_map[$as['fila']][$as['columna']] = $as;
        if (!in_array($as['fila'], $filas)) $filas[] = $as['fila'];
        if ($as['columna'] > $max_col) $max_col = $as['columna'];
    }
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Selección de Asientos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kiosk.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="sala-body">

    <header class="kiosk-header glass-panel" style="border-radius: 0 0 16px 16px; border-top: none;">
        <a href="cartelera.php?id=<?php echo $info['pelicula']; ?>" class="back-link">
            <i class="fas fa-chevron-left"></i> Volver
        </a>
        <div class="header-info">
            <div class="h-title"><?php echo htmlspecialchars($info['pelicula_nombre']); ?></div>
            <div class="h-sub"><?php echo date('g:i A', strtotime($info['hora_valor'])); ?> • <?php echo htmlspecialchars($info['sala_nombre']); ?></div>
        </div>
        <div class="step-indicator">
            Asientos
        </div>
    </header>

    <main class="kiosk-main centered-content">

        <div class="screen-container">
            <div class="screen-curve"></div>
            <div class="screen-glow"></div>
            <span>PANTALLA</span>
        </div>


        <div class="seat-map-container glass-panel" style="background: transparent; border: none; box-shadow: none;">
            <div class="seat-grid" style="grid-template-columns: repeat(<?php echo $max_col; ?>, 35px); gap: 5px;">
                <?php
                foreach ($filas as $f) {
                    for ($c = 1; $c <= $max_col; $c++) {
                        $seat = isset($asientos_map[$f][$c]) ? $asientos_map[$f][$c] : null;

                        if ($seat && $seat['tipo'] != 'PASILLO') {
                            $is_occupied = in_array($seat['id'], $ocupados);
                            $status_class = $is_occupied ? 'occupied' : 'available';
                            $type_class = strtolower($seat['tipo']);
                            $full_label = $f . $seat['num_asiento']; // e.g. A1, B5

                            echo '<button class="seat-btn ' . $status_class . ' ' . $type_class . '" 
                                    data-id="' . $seat['id'] . '" 
                                    data-label="' . $full_label . '"
                                    ' . ($is_occupied ? 'disabled' : '') . '>
                                    ' . $full_label . '
                                  </button>';
                        } else {
                            echo '<div class="seat-gap"></div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="legend-bar glass-panel" style="justify-content: center; background: transparent; border: none; box-shadow: none;">
            <div class="l-item"><span class="seat-dot available normal"></span> Libre</div>
            <div class="l-item"><span class="seat-dot selected"></span> Seleccionado</div>
            <div class="l-item"><span class="seat-dot occupied"></span> Ocupado</div>
        </div>

        <!-- Barra inferior fija -->
        <div class="bottom-action-bar glass-panel" style="border-radius: 0;">
            <div class="selection-summary">
                <span id="seat-count">0</span> Asientos seleccionados
            </div>
            <button class="btn-continue" id="btn-confirm" disabled onclick="procesarReserva()">
                Continuar a la Compra
            </button>
        </div>

    </main>

    <script>
        const FUNCION_ID = <?php echo $id_funcion; ?>;
        const MAX_SEATS = 8;
        let selectedSeats = [];

        // Event Delegation
        const grid = document.querySelector('.seat-grid');
        grid.addEventListener('click', function(e) {
            const btn = e.target.closest('.seat-btn');
            if (!btn || btn.disabled || btn.classList.contains('occupied')) return;

            const id = btn.dataset.id;
            const label = btn.dataset.label;

            if (btn.classList.contains('selected')) {
                btn.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s.id !== id);
            } else {
                if (selectedSeats.length >= MAX_SEATS) {
                    Swal.fire('Límite alcanzado', `Máximo ${MAX_SEATS} asientos por compra`, 'warning');
                    return;
                }
                btn.classList.add('selected');
                selectedSeats.push({
                    id,
                    label
                });
            }
            updateUI();
        });

        function updateUI() {
            document.getElementById('seat-count').innerText = selectedSeats.length;

            const btn = document.getElementById('btn-confirm');
            btn.disabled = selectedSeats.length === 0;
            if (!btn.disabled) {
                btn.style.opacity = '1';
                btn.style.background = 'var(--cinerama-red)';
            } else {
                btn.style.opacity = '0.5';
                btn.style.background = '#333';
            }
        }

        function procesarReserva() {
            if (selectedSeats.length === 0) return;
            const ids = selectedSeats.map(s => s.id).join(',');

            Swal.fire({
                title: 'Procesando...',
                text: 'Reservando tus asientos',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            fetch('kiosk_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=pre_booking&id_funcion=${FUNCION_ID}&seats=${ids}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `resumen.php?id_venta=${data.id_venta}`;
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo completar la reserva', 'error');
                        // Refresh seats if failed
                        updateSeatStatus();
                    }
                })
                .catch(err => Swal.fire('Error', 'Error de conexión', 'error'));
        }

        // --- Polling for Real-Time Synchronization ---
        setInterval(updateSeatStatus, 3000); // Check every 3 seconds

        function updateSeatStatus() {
            fetch(`../api/check_seats.php?id_funcion=${FUNCION_ID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) return;

                    // data is array of objects {id_asiento: "123", estado: "PENDIENTE/PAGADO"}
                    const occupiedIds = new Set(data.map(item => item.id_asiento.toString()));

                    document.querySelectorAll('.seat-btn').forEach(btn => {
                        const id = btn.dataset.id;

                        // Si está ocupado en DB
                        if (occupiedIds.has(id)) {
                            if (!btn.classList.contains('occupied')) {
                                btn.classList.add('occupied');
                                btn.disabled = true;
                                btn.classList.remove('selected');

                                // Si el usuario lo tenía seleccionado, avisar y quitar
                                const idx = selectedSeats.findIndex(s => s.id === id);
                                if (idx !== -1) {
                                    selectedSeats.splice(idx, 1);
                                    updateUI();
                                }
                            }
                        } else {
                            // Si estaba marcado como ocupado pero ya no lo está (e.g. expiró reserva PENDIENTE)
                            if (btn.classList.contains('occupied')) {
                                btn.classList.remove('occupied');
                                btn.disabled = false;
                            }
                        }
                    });
                })
                .catch(err => console.error("Error polling seats:", err));
        }
    </script>

    <style>
        .sala-body {
            background-color: #000;
            /* Match web dark bg */
            color: #fff;
        }

        .header-info {
            text-align: center;
            flex: 1;
        }

        .h-title {
            font-weight: 800;
            font-size: 1.2rem;
        }

        .h-sub {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .step-indicator {
            background: var(--primary);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .centered-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-bottom: 120px;
            /* Space for bottom bar */
        }

        .screen-container {
            margin: 20px 0 40px;
            width: 80%;
            text-align: center;
            position: relative;
        }

        .screen-curve {
            height: 10px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
        }

        .screen-container span {
            color: var(--text-muted);
            letter-spacing: 5px;
            font-size: 0.8rem;
        }


        /* Seat Map */
        .seat-map-container {
            padding: 30px;
            overflow-x: auto;
            max-width: 100%;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }

        .seat-grid {
            display: grid;
            /* grid-template-columns set inline in HTML based on max_col */
            gap: 10px;
            justify-content: center;
        }

        /* Old flex/row styles removed managed by grid now */

        .seat-btn {
            width: 45px;
            height: 45px;
            border-radius: 8px 8px 12px 12px;
            border: none;
            font-weight: bold;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #3c3c4e;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 3px solid #303040;
        }

        .seat-gap {
            width: 45px;
            height: 45px;
        }

        .seat-btn.occupied {
            background: #222;
            color: #444;
            border-color: #111;
            cursor: not-allowed;
        }

        .seat-btn.selected {
            background: var(--primary);
            border-color: #a00000;
            transform: scale(1.1);
            box-shadow: 0 0 15px var(--primary-glow);
            z-index: 2;
        }


        .legend-bar {
            display: flex;
            gap: 20px;
            padding: 15px 30px;
            margin-bottom: 20px;
        }

        .l-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .seat-dot {
            width: 15px;
            height: 15px;
            border-radius: 4px;
            display: block;
        }

        .seat-dot.available {
            background: #3c3c4e;
        }

        .seat-dot.occupied {
            background: #2a2a30;
        }

        .seat-dot.selected {
            background: var(--primary);
        }

        .bottom-action-bar {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            height: auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            border: 1px solid var(--glass-border);
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5);
        }

        .selection-summary {
            font-size: 1.1rem;
        }

        #seat-count {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.4rem;
        }

        .selected-labels {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 5px;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-continue {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-continue:disabled {
            background: #333;
            cursor: not-allowed;
        }
    </style>
</body>

</html>