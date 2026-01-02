<?php
require_once 'includes/front_config.php';

// Modified: Accepts id_venta from GET (created by pre-booking)
$id_venta = isset($_GET['id_venta']) ? (int)$_GET['id_venta'] : 0;

if ($id_venta <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // 1. Validate Sale Status
    $stmtSale = $db->prepare("
        SELECT v.*, f.id as id_funcion, f.id_sala, p.nombre as pelicula, f.fecha, l.nombre as cine, s.local as id_local
        FROM tbl_ventas v
        JOIN tbl_funciones f ON v.id_funcion = f.id
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        WHERE v.id = ? AND v.estado = 'PENDIENTE'
    ");
    $stmtSale->execute([$id_venta]);
    $sale = $stmtSale->fetch();

    if (!$sale) {
        die("La orden no existe o ya expiró (tiempo límite excedido). <a href='index.php'>Volver</a>");
    }

    // 2. Get Booked Seats for this Sale
    $stmtSeats = $db->prepare("SELECT * FROM tbl_boletos WHERE id_venta = ? AND estado = 'ACTIVO'");
    $stmtSeats->execute([$id_venta]);
    $asientosDetalle = $stmtSeats->fetchAll();

    if (empty($asientosDetalle)) {
        die("No hay asientos seleccionados para esta orden.");
    }

    // 3. Get Tariffs
    $stmtTarifas = $db->prepare("SELECT * FROM tbl_tarifa WHERE local = ? ORDER BY precio DESC");
    $stmtTarifas->execute([$sale['id_local']]);
    $tarifas = $stmtTarifas->fetchAll();
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

$page_title = "Pago y Datos";
include 'includes/header_front.php';
?>

<div class="container">
    <h2 class="section-title">Finalizar Compra</h2>
    <div style="text-align: right; color: #555; font-size: 14px; margin-bottom: 10px;">
        Orden: <strong><?php echo htmlspecialchars($sale['codigo']); ?></strong>
    </div>

    <style>
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #333;
            /* Force dark text */
        }

        .card-title {
            color: #d32f2f;
            /* Darker red for title */
            font-weight: bold;
            margin-bottom: 15px;
        }

        .card-text {
            color: #555;
        }

        .tariff-name {
            color: #000;
            font-weight: 700;
        }

        .tariff-price {
            color: #444;
            /* Darker grey */
        }
    </style>

    <form method="POST" action="compra_procesar.php">
        <input type="hidden" name="id_venta" value="<?php echo $id_venta; ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">

            <!-- Columna Izquierda: Tarifas y Datos -->
            <div>
                <!-- 1. Asignar Tarifas (Rediseñado con Contadores) -->
                <div class="card" style="padding: 20px; margin-bottom: 30px;">
                    <h3 class="card-title">1. Selecciona tus Entradas</h3>
                    <p class="card-text">Selecciona la cantidad de entradas según la tarifa. Tienes <strong><?php echo count($asientosDetalle); ?></strong> butacas reservadas.</p>

                    <div id="remaining-alert" style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px;">
                        Faltan asignar <strong id="seats-remaining"><?php echo count($asientosDetalle); ?></strong> entradas.
                    </div>

                    <!-- Hidden inputs for backend compatibility -->
                    <?php
                    $seatIds = [];
                    foreach ($asientosDetalle as $seat) {
                        $seatIds[] = $seat['id'];
                        echo '<input type="hidden" name="tarifa_' . $seat['id'] . '" id="input_seat_' . $seat['id'] . '" value="">';
                    }
                    ?>

                    <!-- Tariff Counters -->
                    <div class="tariff-list">
                        <?php foreach ($tarifas as $t): ?>
                            <div class="tariff-item" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee;">
                                <div>
                                    <div class="tariff-name" style="font-size: 16px; margin-bottom: 4px;"><?php echo htmlspecialchars($t['nombre']); ?></div>
                                    <div class="tariff-price">S/ <?php echo number_format($t['precio'], 2); ?></div>
                                </div>
                                <div class="counter-controls" style="display: flex; align-items: center; gap: 15px;">
                                    <button type="button" class="btn-counter" onclick="updateCount(<?php echo $t['id']; ?>, -1, <?php echo $t['precio']; ?>)">-</button>
                                    <span id="count-<?php echo $t['id']; ?>" style="font-weight: bold; font-size: 18px; width: 30px; text-align: center; color: #000;">0</span>
                                    <button type="button" class="btn-counter" onclick="updateCount(<?php echo $t['id']; ?>, 1, <?php echo $t['precio']; ?>)">+</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Datos del Cliente -->
                <div class="card" style="padding: 20px; margin-bottom: 30px;">
                    <h3 class="card-title">2. Tus Datos</h3>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Nombre Completo</label>
                        <input type="text" name="cliente_nombre" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: none;">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>DNI / RUC</label>
                        <input type="text" name="cliente_doc" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: none;">
                    </div>
                    <div class="form-group">
                        <label>Tipo Comprobante</label>
                        <select name="tipo_comprobante" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: none;">
                            <option value="BOLETA">Boleta</option>
                            <option value="FACTURA">Factura</option>
                        </select>
                    </div>
                </div>

                <!-- 3. Medio de Pago -->
                <div class="card" style="padding: 20px;">
                    <h3 class="card-title">3. Pago</h3>
                    <div style="display: flex; gap: 20px;">
                        <!-- Ocultamos YAPE por solicitud del usuario "lo de yape no lo consideres" -->
                        <!-- 
                        <label style="cursor: pointer;">
                            <input type="radio" name="medio_pago" value="YAPE"> Yape / Plin
                        </label> 
                        -->
                        <label style="cursor: pointer;">
                            <input type="radio" name="medio_pago" value="TARJETA" checked> Tarjeta Crédito/Débito
                        </label>
                        <label style="cursor: pointer;">
                            <input type="radio" name="medio_pago" value="EFECTIVO"> Efectivo (Presencial)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Resumen -->
            <div>
                <div class="card" style="padding: 20px; position: sticky; top: 20px;">
                    <h3 class="card-title">Resumen</h3>
                    <p><strong>Película:</strong> <?php echo htmlspecialchars($sale['pelicula']); ?></p>
                    <p><strong>Cine:</strong> <?php echo htmlspecialchars($sale['cine']); ?></p>
                    <p><strong>Cantidad:</strong> <?php echo count($asientosDetalle); ?> butacas</p>

                    <hr style="border-color: #333;">

                    <div style="font-size: 24px; font-weight: bold; text-align: right; margin-top: 10px; color: #e50914;">
                        Total: S/ <span id="total-amount">0.00</span>
                    </div>

                    <button type="submit" id="btn-confirmar" class="btn" style="width: 100%; margin-top: 20px; font-size: 18px;" disabled>CONFIRMAR PAGO</button>

                    <p style="font-size: 12px; color: #666; margin-top: 10px; text-align: center;">
                        Tienes 10 minutos para completar tu compra.
                    </p>
                </div>
            </div>

        </div>
    </form>
</div>

<style>
    .btn-counter {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        border: none;
        background: #444;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }

    .btn-counter:hover {
        background: #e50914;
    }
</style>

<script>
    const totalSeats = <?php echo count($asientosDetalle); ?>;
    const seatIds = <?php echo json_encode($seatIds); ?>;
    // Object to track counts per tariff
    const counts = {};
    let currentTotal = 0;

    function updateCount(tariffId, delta, price) {
        if (!counts[tariffId]) counts[tariffId] = 0;

        const futureCount = counts[tariffId] + delta;

        // Validation: No negatives
        if (futureCount < 0) return;

        // Validation: Cannot exceed total seats
        const futureTotal = getTotalSelected() + delta;
        if (futureTotal > totalSeats) {
            alert("No puedes seleccionar más entradas que butacas reservadas.");
            return;
        }

        // Apply change
        counts[tariffId] = futureCount;
        document.getElementById(`count-${tariffId}`).innerText = counts[tariffId];

        // Update UI Logic
        updateUI();
    }

    function getTotalSelected() {
        return Object.values(counts).reduce((a, b) => a + b, 0);
    }

    function updateUI() {
        // 1. Calculate Total Price
        let totalPrice = 0;
        // We need to re-scan because we didn't store prices in `counts`
        // Simplified: Loop inputs or pass price in onclick (done above, but need global calc)
        // Better: iterate DOM counters or store price in object.
        // Let's refactor `updateCount` slightly to be simpler? No, let's just recalculate.

        // We will loop through all available tariffs shown in DOM
        // This is safe because we only have a few tariffs.
        // Or actually, simple approach:
        // Just recreate the distribution logic.

        distributeTariffs();
    }

    function distributeTariffs() {
        let assigned = 0;
        let totalPrice = 0;

        // Clear all inputs first
        seatIds.forEach(sid => {
            document.getElementById(`input_seat_${sid}`).value = "";
        });

        // Loop through counts and assign to seats
        // We iterate through our `counts` object
        for (const [tId, qty] of Object.entries(counts)) {
            // Find price from DOM (hacky but works without complex JS object)
            // Or better, let's just trust variable passing if possible. 
            // Actually, we need price for total.
            // Let's get price from the button attribute? No.

            // Let's verify prices from select options? No select options anymore.
            // We can assume user won't hack prices (server verifies anyway).
            // We need to calculate total visual price using the passed price is tricky if not stored.

            // Let's store prices in a map on init.
        }
    }
</script>

<!-- REPLACING SCRIPT WITH ROBUST VERSION -->
<script>
    const TOTAL_SEATS = <?php echo count($asientosDetalle); ?>;
    const SEAT_IDS = <?php echo json_encode($seatIds); ?>;

    // Store tariff info: { id: { price: 10.00, count: 0 } }
    const tariffData = {};

    <?php foreach ($tarifas as $t): ?>
        tariffData[<?php echo $t['id']; ?>] = {
            price: <?php echo $t['precio']; ?>,
            count: 0
        };
    <?php endforeach; ?>

    function updateCount(id, delta) {
        const currentTotal = Object.values(tariffData).reduce((sum, item) => sum + item.count, 0);

        if (delta > 0 && currentTotal >= TOTAL_SEATS) {
            alert(`Solo puedes seleccionar ${TOTAL_SEATS} entradas.`);
            return;
        }

        if (tariffData[id].count + delta < 0) return;

        tariffData[id].count += delta;
        document.getElementById(`count-${id}`).innerText = tariffData[id].count;

        // Refresh State
        refreshState();
    }

    function refreshState() {
        let totalQty = 0;
        let totalAmount = 0;
        let seatIndex = 0;

        // Reset all hidden inputs
        SEAT_IDS.forEach(sid => document.getElementById(`input_seat_${sid}`).value = "");

        // Assign tariffs to seats sequentially
        for (const [tid, data] of Object.entries(tariffData)) {
            for (let i = 0; i < data.count; i++) {
                if (seatIndex < SEAT_IDS.length) {
                    const sid = SEAT_IDS[seatIndex];
                    document.getElementById(`input_seat_${sid}`).value = tid;
                    seatIndex++;
                }
                totalAmount += data.price;
            }
            totalQty += data.count;
        }

        document.getElementById('total-amount').innerText = totalAmount.toFixed(2);
        document.getElementById('seats-remaining').innerText = TOTAL_SEATS - totalQty;

        // Button State
        const btn = document.getElementById('btn-confirmar');
        if (totalQty === TOTAL_SEATS) {
            btn.disabled = false;
            document.getElementById('remaining-alert').style.display = 'none';
        } else {
            btn.disabled = true;
            document.getElementById('remaining-alert').style.display = 'block';
        }
    }
</script>

<?php include 'includes/footer_front.php'; ?>