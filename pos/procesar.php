<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

$id_funcion = $_POST['id_funcion'] ?? 0;
$asientos_ids = $_POST['asientos'] ?? '';

if (!$id_funcion || !$asientos_ids) {
    header("Location: dashboard.php");
    exit;
}

// Get Function Info
$stmt = $db->prepare("
    SELECT f.*, p.nombre as pelicula, s.nombre as sala, l.nombre as cine, l.id as id_local, h.hora as hora_inicio
    FROM tbl_funciones f
    JOIN tbl_pelicula p ON f.id_pelicula = p.id
    JOIN tbl_sala s ON f.id_sala = s.id
    JOIN tbl_locales l ON s.local = l.id
    JOIN tbl_hora h ON f.id_hora = h.id
    WHERE f.id = ?
");
$stmt->execute([$id_funcion]);
$funcion = $stmt->fetch();

// Determine Day of Week for Tariff (l, m, x, j, v, s, d)
$dias = ['d', 'l', 'm', 'x', 'j', 'v', 's'];
$dia_semana_num = date('w', strtotime($funcion['fecha'])); // 0 (Sun) - 6 (Sat)
$dia_key = $dias[$dia_semana_num];

// Get Valid Tariffs
// Logic: Tariffs for this Local AND (Active for this day OR Active always/0)
// Simplified: Get all active tariffs for this local.
$stmt = $db->prepare("SELECT * FROM tbl_tarifa WHERE local = ? ORDER BY precio DESC");
$stmt->execute([$funcion['id_local']]);
$tarifas_raw = $stmt->fetchAll();

// Filter tariffs that apply to today (column of day = '1') OR generic (all days 0?)
// Actually, let's just show all available tariffs for the cashier to choose.
// But we can try to pre-select a "Day Promo" if it matches.
$tarifas = $tarifas_raw;
$default_tarifa_id = $tarifas[0]['id'] ?? 0;

foreach ($tarifas as $t) {
    if ($t[$dia_key] == '1') {
        $default_tarifa_id = $t['id'];
        break;
    }
}

// Get Selected Seats Info
$ids_array = explode(',', $asientos_ids);
$placeholders = str_repeat('?,', count($ids_array) - 1) . '?';
$stmtAsientos = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE id IN ($placeholders) ORDER BY fila, num_asiento");
$stmtAsientos->execute($ids_array);
$asientos_detalles = $stmtAsientos->fetchAll();

include 'includes/header.php';
?>

<h2>Resumen de Venta</h2>
<div class="row" style="display: flex; gap: 20px;">
    <div style="flex: 2;">
        <div class="card" style="background: white; padding: 20px; border-radius: 8px;">
            <h3><?php echo htmlspecialchars($funcion['pelicula']); ?></h3>
            <p><?php echo htmlspecialchars($funcion['cine'] . ' - ' . $funcion['sala']); ?></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($funcion['fecha'] . ' ' . $funcion['hora_inicio'])); ?></p>

            <form action="finalizar_venta.php" method="POST" id="checkout-form">
                <input type="hidden" name="id_funcion" value="<?php echo $id_funcion; ?>">

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <th style="text-align: left; padding: 10px;">Asiento</th>
                            <th style="text-align: left; padding: 10px;">Tipo</th>
                            <th style="text-align: left; padding: 10px;">Tarifa</th>
                            <th style="text-align: right; padding: 10px;">Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asientos_detalles as $idx => $asiento): ?>
                            <tr>
                                <td style="padding: 10px;">
                                    <?php echo $asiento['fila'] . $asiento['num_asiento']; ?>
                                    <input type="hidden" name="asientos[<?php echo $idx; ?>][id]" value="<?php echo $asiento['id']; ?>">
                                    <input type="hidden" name="asientos[<?php echo $idx; ?>][nombre]" value="<?php echo $asiento['fila'] . $asiento['num_asiento']; ?>">
                                </td>
                                <td style="padding: 10px;"><?php echo $asiento['tipo']; ?></td>
                                <td style="padding: 10px;">
                                    <select name="asientos[<?php echo $idx; ?>][id_tarifa]" class="tarifa-select" onchange="updateTotal()" style="padding: 5px;">
                                        <?php foreach ($tarifas as $t): ?>
                                            <option value="<?php echo $t['id']; ?>" data-precio="<?php echo $t['precio']; ?>" <?php echo ($t['id'] == $default_tarifa_id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($t['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="text-align: right; padding: 10px;">
                                    S/ <span class="price-display">0.00</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid #ddd; font-weight: bold; font-size: 1.2rem;">
                            <td colspan="3" style="padding: 20px 10px; text-align: right;">TOTAL:</td>
                            <td style="padding: 20px 10px; text-align: right;">S/ <span id="grand-total">0.00</span></td>
                        </tr>
                    </tfoot>
                </table>

        </div>
    </div>

    <div style="flex: 1;">
        <div class="card" style="background: white; padding: 20px; border-radius: 8px;">
            <h3>Datos del Cliente</h3>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">DNI / RUC (Opcional)</label>
                <input type="text" name="cliente_doc" id="cliente_doc" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Nombre Completo</label>
                <input type="text" name="cliente_nombre" id="cliente_nombre" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" value="CLIENTE GENERICO">
            </div>

            <h3>Pago</h3>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px;">Tipo Comprobante</label>
                <select name="tipo_comprobante" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="BOLETA">BOLETA DE VENTA</option>
                    <option value="FACTURA">FACTURA</option>
                </select>
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 15px;">
                <label style="display: block; font-weight: bold; margin-bottom: 10px;">Medios de Pago</label>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>EFECTIVO (S/)</span>
                    <input type="number" step="0.01" name="pago_efectivo" class="pago-input" style="width: 100px; padding: 5px;" placeholder="0.00">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>VISA/MC (S/)</span>
                    <input type="number" step="0.01" name="pago_tarjeta" class="pago-input" style="width: 100px; padding: 5px;" placeholder="0.00">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>YAPE (S/)</span>
                    <input type="number" step="0.01" name="pago_yape" class="pago-input" style="width: 100px; padding: 5px;" placeholder="0.00">
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>PLIN (S/)</span>
                    <input type="number" step="0.01" name="pago_plin" class="pago-input" style="width: 100px; padding: 5px;" placeholder="0.00">
                </div>

                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccc; font-weight: bold; display: flex; justify-content: space-between;">
                    <span>Pagado:</span>
                    <span id="total-pagado" style="color: #c01820;">S/ 0.00</span>
                </div>
                <div style="margin-top: 5px; font-weight: bold; display: flex; justify-content: space-between;">
                    <span>Faltante:</span>
                    <span id="total-faltante" style="color: #c01820;">S/ 0.00</span>
                </div>
            </div>

            <input type="hidden" name="medio_pago" id="medio_pago_resumen" value="EFECTIVO">

            <button type="submit" id="btn-cobrar" disabled style="width: 100%; padding: 15px; background: #ccc; color: white; border: none; border-radius: 4px; font-size: 1.2rem; cursor: not-allowed; margin-top: 10px;">
                <i class="fas fa-money-bill-wave"></i> COBRAR
            </button>
            </form>
        </div>
    </div>
</div>

<script>
    function updateTotal() {
        let total = 0;
        const selects = document.querySelectorAll('.tarifa-select');
        selects.forEach(select => {
            const row = select.closest('tr');
            const price = parseFloat(select.options[select.selectedIndex].dataset.precio);
            row.querySelector('.price-display').innerText = price.toFixed(2);
            total += price;
        });
        document.getElementById('grand-total').innerText = total.toFixed(2);
        validatePayments(); // Re-validate
    }

    function validatePayments() {
        // Calculate Total Required
        let totalRequired = parseFloat(document.getElementById('grand-total').innerText);

        // Calculate Total Input
        let totalPaid = 0;
        let inputs = document.querySelectorAll('.pago-input');
        inputs.forEach(inp => {
            let val = parseFloat(inp.value) || 0;
            totalPaid += val;
        });

        document.getElementById('total-pagado').innerText = 'S/ ' + totalPaid.toFixed(2);

        let faltante = totalRequired - totalPaid;
        // Fix float precision
        faltante = Math.round(faltante * 100) / 100;

        let faltanteSpan = document.getElementById('total-faltante');
        let btn = document.getElementById('btn-cobrar');

        if (Math.abs(faltante) < 0.01) { // Exact amount
            faltanteSpan.innerText = 'S/ 0.00';
            faltanteSpan.style.color = 'green';
            btn.disabled = false;
            btn.style.background = '#28a745';
            btn.style.cursor = 'pointer';

            // Build Summary String
            let summary = [];
            inputs.forEach(inp => {
                let val = parseFloat(inp.value) || 0;
                if (val > 0) {
                    let name = inp.name.replace('pago_', '').toUpperCase();
                    if (name == 'TARJETA') name = 'VISA';
                    summary.push(name + ': ' + val.toFixed(2));
                }
            });
            document.getElementById('medio_pago_resumen').value = summary.join(' + ');

        } else if (faltante > 0) { // Still missing money
            faltanteSpan.innerText = 'S/ ' + faltante.toFixed(2);
            faltanteSpan.style.color = '#c01820';
            btn.disabled = true;
            btn.style.background = '#ccc';
            btn.style.cursor = 'not-allowed';
        } else { // Overpaid (Change)
            faltanteSpan.innerText = 'VUELTO: S/ ' + Math.abs(faltante).toFixed(2);
            faltanteSpan.style.color = 'blue';
            // Allow overpayment? Assuming yes for cash, but usually exact. For now allow.
            // Actually, usually POS requires exact or change calc. Let's allow.
            btn.disabled = false;
            btn.style.background = '#28a745';
            btn.style.cursor = 'pointer';

            // Adjust summary for overpayment? Or just record cash given.
            let summary = [];
            inputs.forEach(inp => {
                let val = parseFloat(inp.value) || 0;
                if (val > 0) {
                    let name = inp.name.replace('pago_', '').toUpperCase();
                    if (name == 'TARJETA') name = 'VISA';
                    summary.push(name + ': ' + val.toFixed(2));
                }
            });
            document.getElementById('medio_pago_resumen').value = summary.join(' + ');
        }
    }

    // Event Listeners
    document.querySelectorAll('.pago-input').forEach(input => {
        input.addEventListener('input', validatePayments);
    });

    // Init
    updateTotal();
</script>

<?php include 'includes/footer.php'; ?>