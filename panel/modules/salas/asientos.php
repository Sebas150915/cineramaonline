<?php
require_once '../../config/config.php';

$page_title = "Gestionar Asientos";

// Obtener ID de sala
$sala_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sala_id <= 0) {
    showAlert('error', 'Error', 'ID de sala inválido');
    redirect('index.php');
}

// Obtener información de la sala
try {
    $stmt = $db->prepare("
        SELECT s.*, l.nombre as local_nombre 
        FROM tbl_sala s
        LEFT JOIN tbl_locales l ON s.local = l.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sala_id]);
    $sala = $stmt->fetch();

    if (!$sala) {
        showAlert('error', 'Error', 'Sala no encontrada');
        redirect('index.php');
    }
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener la sala: ' . $e->getMessage());
    redirect('index.php');
}

// Obtener asientos existentes
try {
    $stmt = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
    $stmt->execute([$sala_id]);
    $asientos_db = $stmt->fetchAll();

    // Organizar asientos por fila y número
    $asientos = [];
    foreach ($asientos_db as $asiento) {
        $asientos[$asiento['fila']][$asiento['columna']] = $asiento;
    }
} catch (PDOException $e) {
    $asientos = [];
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] == 'generar_asientos') {
        $filas = (int)$_POST['filas'];
        $columnas = (int)$_POST['columnas'];

        try {
            // Eliminar asientos existentes
            $stmt = $db->prepare("DELETE FROM tbl_sala_asiento WHERE idsala = ?");
            $stmt->execute([$sala_id]);

            // Obtener el local de la sala
            $local_id = $sala['local'];

            // Generar nuevos asientos
            $letras = range('A', 'Z');
            for ($f = 0; $f < $filas; $f++) {
                for ($c = 1; $c <= $columnas; $c++) {
                    $stmt = $db->prepare("
                        INSERT INTO tbl_sala_asiento (idsala, local, fila, columna, num_asiento, tipo, estado) 
                        VALUES (?, ?, ?, ?, ?, 'NORMAL', 'activo')
                    ");
                    $stmt->execute([$sala_id, $local_id, $letras[$f], $c, (string)$c]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Asientos generados correctamente']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] == 'cambiar_tipo') {
        $fila = sanitize($_POST['fila']);
        $numero = (int)$_POST['numero'];
        $tipo = sanitize($_POST['tipo']);

        try {
            // Actualizar el tipo del asiento (incluyendo PASILLO)
            $stmt = $db->prepare("
                UPDATE tbl_sala_asiento 
                SET tipo = ?, updated_at = CURRENT_TIMESTAMP
                WHERE idsala = ? AND fila = ? AND columna = ?
            ");
            $stmt->execute([$tipo, $sala_id, $fila, $numero]);

            echo json_encode(['success' => true, 'message' => 'Tipo actualizado']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }


    if ($_POST['action'] == 'renumerar_asiento') {
        $fila_actual = sanitize($_POST['fila_actual']);
        $numero_actual = (int)$_POST['numero_actual']; // Physical column index
        $nuevo_rotulo = sanitize($_POST['nuevo_rotulo']); // Visual label string

        try {
            // Actualizar SOLO el número visual (etiqueta), manteniendo la posición física
            $stmt = $db->prepare("
                UPDATE tbl_sala_asiento 
                SET num_asiento = ?, updated_at = CURRENT_TIMESTAMP
                WHERE idsala = ? AND fila = ? AND columna = ?
            ");
            $stmt->execute([$nuevo_rotulo, $sala_id, $fila_actual, $numero_actual]);

            echo json_encode(['success' => true, 'message' => 'Etiqueta actualizada correctamente']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<style>
    .seat-map {
        display: inline-block;
        background: #f8f9fa;
        padding: 30px;
        border-radius: 12px;
        margin: 20px 0;
    }

    .seat-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        align-items: center;
    }

    .row-label {
        width: 30px;
        text-align: center;
        font-weight: bold;
        color: #666;
    }

    .seat {
        width: 35px;
        height: 35px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .seat:hover:not(.PASILLO) {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .seat.NORMAL {
        background: #28a745;
        color: white;
    }

    .seat.VIP {
        background: #ffc107;
        color: #333;
    }

    .seat.DISCAPACITADO {
        background: #17a2b8;
        color: white;
    }

    .seat.PASILLO {
        background: transparent;
        border: 2px dashed #ddd;
        cursor: pointer;
    }

    .seat.bloqueado {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .screen {
        background: linear-gradient(to bottom, #333, #666);
        color: white;
        text-align: center;
        padding: 15px;
        border-radius: 50% 50% 0 0 / 20% 20% 0 0;
        margin-bottom: 30px;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .legend {
        display: flex;
        gap: 20px;
        margin: 20px 0;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-box {
        width: 25px;
        height: 25px;
        border-radius: 6px;
    }

    .controls {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Mapa de Asientos - <?php echo htmlspecialchars($sala['nombre']); ?></h1>
            <p class="page-subtitle"><?php echo htmlspecialchars($sala['local_nombre']); ?> | Capacidad: <?php echo $sala['capacidad']; ?> asientos</p>
        </div>
        <div>
            <a href="index.php?local=<?php echo $sala['local']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Salas
            </a>
        </div>
    </div>

    <!-- Controles -->
    <div class="controls">
        <h3 style="margin-bottom: 15px;">Generar Mapa de Asientos</h3>
        <div style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label>Filas (A-Z)</label>
                <input type="number" id="filas" class="form-control" min="1" max="26" value="<?php echo $sala['filas'] ?: 10; ?>" style="width: 100px;">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Asientos por Fila</label>
                <input type="number" id="columnas" class="form-control" min="1" value="<?php echo $sala['columnas'] ?: 15; ?>" style="width: 100px;">
            </div>
            <button onclick="generarAsientos()" class="btn btn-primary">
                <i class="fas fa-sync"></i> Generar/Regenerar Mapa
            </button>
        </div>
        <small style="color: #666; display: block; margin-top: 10px;">
            <i class="fas fa-info-circle"></i> Esto eliminará el mapa actual y creará uno nuevo. Luego podrás personalizar cada asiento.
        </small>
    </div>

    <!-- Leyenda -->
    <div class="legend">
        <div class="legend-item">
            <div class="legend-box" style="background: #28a745;"></div>
            <span>Normal</span>
        </div>
        <div class="legend-item">
            <div class="legend-box" style="background: #ffc107;"></div>
            <span>VIP</span>
        </div>
        <div class="legend-item">
            <div class="legend-box" style="background: #17a2b8;"></div>
            <span>Discapacitado</span>
        </div>
        <div class="legend-item">
            <div class="legend-box" style="background: transparent; border: 2px dashed #ddd;"></div>
            <span>Pasillo</span>
        </div>
    </div>

    <!-- Mapa de Asientos -->
    <div style="text-align: center;">
        <div class="seat-map">
            <div class="screen">PANTALLA</div>
            <div id="mapa-asientos">
                <?php if (empty($asientos)): ?>
                    <p style="color: #666; padding: 40px;">
                        <i class="fas fa-info-circle"></i> No hay asientos configurados. Genera el mapa usando los controles de arriba.
                    </p>
                <?php else: ?>
                    <?php
                    // Obtener el rango completo de filas y columnas desde la BD
                    $filas_existentes = array_keys($asientos);

                    // Determinar la columna máxima
                    $max_columna = 0;
                    $min_columna = PHP_INT_MAX;
                    foreach ($asientos as $fila_asientos) {
                        if (!empty($fila_asientos)) {
                            $max_columna = max($max_columna, max(array_keys($fila_asientos)));
                            $min_columna = min($min_columna, min(array_keys($fila_asientos)));
                        }
                    }

                    // Si no hay columnas, establecer valores por defecto
                    if ($min_columna == PHP_INT_MAX) {
                        $min_columna = 1;
                    }

                    // Renderizar cada fila que existe en la BD
                    foreach ($filas_existentes as $letra):
                    ?>
                        <div class="seat-row">
                            <div class="row-label"><?php echo $letra; ?></div>
                            <?php for ($num = $min_columna; $num <= $max_columna; $num++): ?>
                                <?php if (isset($asientos[$letra][$num])):
                                    $asiento = $asientos[$letra][$num];
                                ?>
                                    <div class="seat <?php echo $asiento['tipo']; ?> <?php echo ($asiento['estado'] == '0') ? 'bloqueado' : ''; ?>"
                                        data-fila="<?php echo $letra; ?>"
                                        data-numero="<?php echo $num; ?>"
                                        data-rotulo="<?php echo $asiento['num_asiento']; ?>"
                                        data-tipo="<?php echo $asiento['tipo']; ?>"
                                        onclick="cambiarAsiento(this)">
                                        <?php if ($asiento['tipo'] != 'PASILLO'): ?>
                                            <?php echo $letra . $asiento['num_asiento']; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="width: 35px;"></div>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <div class="row-label"><?php echo $letra; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function generarAsientos() {
        const filas = parseInt(document.getElementById('filas').value);
        const columnas = parseInt(document.getElementById('columnas').value);

        if (!filas || !columnas || filas < 1 || columnas < 1) {
            Swal.fire('Error', 'Ingresa valores válidos', 'error');
            return;
        }

        Swal.fire({
            title: '¿Generar nuevo mapa?',
            text: 'Esto eliminará el mapa actual y creará uno nuevo',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('asientos.php?id=<?php echo $sala_id; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=generar_asientos&filas=${filas}&columnas=${columnas}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }

    function cambiarAsiento(element) {
        const fila = element.dataset.fila;
        const numero = element.dataset.numero; // Physical Index
        const rotulo = element.dataset.rotulo || numero; // Visual Label
        const tipoActual = element.dataset.tipo;

        Swal.fire({
            title: `Asiento ${fila}${rotulo}`,
            html: `
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
                <button onclick="cambiarTipo('${fila}', ${numero}, 'NORMAL')" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-couch"></i> Normal
                </button>
                <button onclick="cambiarTipo('${fila}', ${numero}, 'VIP')" class="btn btn-warning" style="width: 100%;">
                    <i class="fas fa-star"></i> VIP
                </button>
                <button onclick="cambiarTipo('${fila}', ${numero}, 'DISCAPACITADO')" class="btn btn-info" style="width: 100%;">
                    <i class="fas fa-wheelchair"></i> Discapacitado
                </button>
                <button onclick="cambiarTipo('${fila}', ${numero}, 'PASILLO')" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-arrows-alt-h"></i> Convertir en Pasillo
                </button>
                <hr style="margin: 10px 0;">
                <button onclick="renumerarAsiento('${fila}', ${numero}, '${rotulo}')" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-edit"></i> Cambiar Etiqueta
                </button>
            </div>
        `,
            showConfirmButton: false,
            showCloseButton: true
        });
    }

    window.cambiarTipo = function(fila, numero, tipo) {
        fetch('asientos.php?id=<?php echo $sala_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=cambiar_tipo&fila=${fila}&numero=${numero}&tipo=${tipo}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.close();
                    location.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
    }

    window.renumerarAsiento = function(filaActual, numeroActual, rotuloActual) {
        Swal.fire({
            title: `Editar Asiento ${filaActual}${rotuloActual}`,
            html: `
                <div style="text-align: left; margin-top: 20px;">
                    <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                        Cambia el número/texto que se muestra en el asiento.
                    </p>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Etiqueta Visual:</label>
                        <input type="text" id="nuevo_rotulo" class="form-control" value="${rotuloActual}" 
                               placeholder="Ej: 1, 10, VIP-1..."
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const input = document.getElementById('nuevo_rotulo');
                // Intentar recuperar el valor actual real si es posible, sino usa el index
                // Nota: numeroActual es el indice de columna fisica, podria no ser el rotulo actual.
                // Lo corregiremos en la llamada.
                input.focus();
                input.select();
            },
            preConfirm: () => {
                const nuevoRotulo = document.getElementById('nuevo_rotulo').value.trim();

                if (!nuevoRotulo) {
                    Swal.showValidationMessage('La etiqueta no puede estar vacía');
                    return false;
                }

                return {
                    nuevoRotulo
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const {
                    nuevoRotulo
                } = result.value;

                fetch('asientos.php?id=<?php echo $sala_id; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=renumerar_asiento&fila_actual=${filaActual}&numero_actual=${numeroActual}&nuevo_rotulo=${nuevoRotulo}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Éxito', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
            }
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>