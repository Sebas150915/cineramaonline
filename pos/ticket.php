<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

$id_venta = $_GET['id'] ?? 0;

if (!$id_venta) {
    die("Venta no válida.");
}

// Get Sale Data
$stmt = $db->prepare("
    SELECT v.*, f.fecha, h.hora as hora_inicio, p.nombre as pelicula, p.censura, s.nombre as sala, l.nombre as cine, l.direccion
    FROM tbl_ventas v
    JOIN tbl_funciones f ON v.id_funcion = f.id
    JOIN tbl_hora h ON f.id_hora = h.id
    JOIN tbl_pelicula p ON f.id_pelicula = p.id
    JOIN tbl_sala s ON f.id_sala = s.id
    JOIN tbl_locales l ON s.local = l.id
    WHERE v.id = ?
");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch();

if (!$venta) die("Venta no encontrada.");

// Get Tickets
$stmtB = $db->prepare("
    SELECT b.*, t.nombre as tarifa_nombre
    FROM tbl_boletos b
    JOIN tbl_tarifa t ON b.id_tarifa = t.id
    WHERE b.id_venta = ?
");
$stmtB->execute([$id_venta]);
$boletos = $stmtB->fetchAll();

// Group tickets by tariff for cleaner display (SUNAT requirement usually groups items)
$items = [];
foreach ($boletos as $b) {
    $key = $b['id_tarifa'] . '-' . $b['precio'];
    if (!isset($items[$key])) {
        $items[$key] = [
            'nombre' => $b['tarifa_nombre'],
            'precio' => $b['precio'],
            'cantidad' => 0,
            'total' => 0
        ];
    }
    $items[$key]['cantidad']++;
    $items[$key]['total'] += $b['precio'];
}

// Parse Medio de Pago (if JSON or String)
// We saved it as "EFECTIVO: 10 + VISA: 20"
$medio_pago_str = $venta['medio_pago'];
$pagos = explode(' + ', $medio_pago_str);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ticket #<?php echo $venta['codigo']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Monospaced for receipt look */
            font-size: 11px;
            width: 72mm;
            /* Standard Thermal Paper Width */
            margin: 0 auto;
            padding: 5px;
            background: #fff;
        }

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .header {
            margin-bottom: 10px;
        }

        .logo {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }

        .table-items {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table-items th {
            text-align: left;
            border-bottom: 1px solid #000;
        }

        .table-items td {
            vertical-align: top;
        }

        .total-box {
            margin-top: 10px;
            font-size: 13px;
        }

        .footer {
            margin-top: 15px;
            font-size: 10px;
            color: #333;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <!-- HEADER -->
    <div class="center header">
        <div class="logo bold">CINERAMA</div>
        <div><?php echo strtoupper($venta['cine']); ?></div>
        <div><?php echo $venta['direccion']; ?></div>
        <div>RUC: 20601234567</div>
        <div class="bold"><?php echo $venta['tipo_comprobante']; ?> ELECTRÓNICA</div>
        <div><?php echo $venta['codigo']; ?></div>
    </div>

    <!-- INFO VENTA -->
    <div class="line"></div>
    <div class="left">
        <div><span class="bold">FECHA:</span> <?php echo date('d/m/Y H:i'); ?></div>
        <div><span class="bold">CAJERO:</span> <?php echo substr($_SESSION['pos_user_usuario'], 0, 15); ?></div>
        <div><span class="bold">CLIENTE:</span> <?php echo substr($venta['cliente_nombre'], 0, 20); ?></div>
        <?php if ($venta['cliente_doc']): ?>
            <div><span class="bold">DOC:</span> <?php echo $venta['cliente_doc']; ?></div>
        <?php endif; ?>
    </div>
    <div class="line"></div>

    <!-- MOVIE INFO -->
    <div class="center">
        <div class="bold" style="font-size: 13px;"><?php echo strtoupper($venta['pelicula']); ?></div>
        <div>SALA: <?php echo $venta['sala']; ?></div>
        <div>FUNC: <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?> - <?php echo date('H:i', strtotime($venta['hora_inicio'])); ?></div>
    </div>
    <div class="line"></div>

    <!-- ITEMS -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 10%;">CANT</th>
                <th style="width: 50%;">DESCRIPCIÓN</th>
                <th style="width: 20%; text-align: right;">P.U.</th>
                <th style="width: 20%; text-align: right;">IMP.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="center"><?php echo $item['cantidad']; ?></td>
                    <td><?php echo $item['nombre']; ?></td>
                    <td class="right"><?php echo number_format($item['precio'], 2); ?></td>
                    <td class="right"><?php echo number_format($item['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="line"></div>

    <!-- TOTALS -->
    <div class="right total-box bold">
        <div>OP. GRAVADA: S/ <?php echo number_format($venta['total'] / 1.18, 2); ?></div>
        <div>I.G.V. (18%): S/ <?php echo number_format($venta['total'] - ($venta['total'] / 1.18), 2); ?></div>
        <div style="font-size: 16px; margin-top: 5px;">TOTAL: S/ <?php echo number_format($venta['total'], 2); ?></div>
    </div>

    <div class="line"></div>

    <!-- PAYMENTS -->
    <div class="left">
        <div class="bold">PAGOS:</div>
        <?php foreach ($pagos as $p): ?>
            <div>- <?php echo $p; ?></div>
        <?php endforeach; ?>
    </div>

    <!-- FOOTER -->
    <div class="center footer">
        <div class="bold">ASIENTOS:</div>
        <div style="font-size: 12px; margin-bottom: 5px;">
            <?php
            $asientos_list = array_map(function ($b) {
                return $b['fila'] . $b['numero'];
            }, $boletos);
            echo implode(', ', $asientos_list);
            ?>
        </div>

        <div style="margin-top: 10px;">
            Representación impresa de la<br>
            <?php echo $venta['tipo_comprobante']; ?> ELECTRÓNICA<br>
            Consulte su documento en<br>
            www.cinerama.com.pe
        </div>

        <div style="margin-top: 10px;">
            <canvas id="qr"></canvas>
        </div>

        <div style="margin-top: 5px;">Gracias por su preferencia</div>
    </div>

    <div class="center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px;">IMPRIMIR</button>
        <a href="dashboard.php" style="background: #333; color: white; padding: 10px; text-decoration: none;">NUEVA VENTA</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        (function() {
            var qr = new QRious({
                element: document.getElementById('qr'),
                value: 'https://cinerama.com.pe/comprobante?id=<?php echo $venta['codigo']; ?>',
                size: 100
            });
        })();
    </script>
</body>

</html>
```