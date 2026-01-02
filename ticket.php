<?php
require_once 'includes/front_config.php';

$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : '';

function numeroALetras($amount)
{
    $partes = explode('.', number_format($amount, 2, '.', ''));
    $entero = (int)$partes[0];
    $decimal = $partes[1];

    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas_puras = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $decenas_irregular = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];

    $texto = '';

    if ($entero == 0) $texto = 'CERO';
    else if ($entero < 10) $texto = $unidades[$entero];
    else if ($entero < 20) $texto = $decenas_irregular[$entero - 10];
    else if ($entero < 100) {
        $decena = floor($entero / 10);
        $unidad = $entero % 10;
        $texto = $decenas_puras[$decena];
        if ($unidad > 0) $texto .= ($decena == 2 ? 'I' : ' Y ') . $unidades[$unidad];
        // corrección simple para "veintiuno" etc si fuera necesario, pero "VEINTE Y UNO" es aceptable o "VEINTIUNO"
        if ($decena == 2 && $unidad > 0) $texto = 'VEINTI' . $unidades[$unidad];
    } else if ($entero == 100) {
        $texto = 'CIEN';
    } else if ($entero < 1000) {
        $centena = floor($entero / 100);
        $resto = $entero % 100;
        $texto = ($centena == 1 ? 'CIENTO' : $unidades[$centena] . 'CIENTOS');
        if ($entero >= 500 && $entero < 600) $texto = 'QUINIENTOS';
        if ($entero >= 700 && $entero < 800) $texto = 'SETECIENTOS';
        if ($entero >= 900 && $entero < 1000) $texto = 'NOVECIENTOS';

        if ($resto > 0) {
            // Recurse essentially but simple here
            $d = floor($resto / 10);
            $u = $resto % 10;
            $texto .= ' ';
            if ($resto < 10) $texto .= $unidades[$resto];
            else if ($resto < 20) $texto .= $decenas_irregular[$resto - 10];
            else {
                $texto .= $decenas_puras[$d];
                if ($u > 0) {
                    if ($d == 2) $texto = substr($texto, 0, -1) . 'I' . $unidades[$u]; // VEINTI...
                    else $texto .= ' Y ' . $unidades[$u];
                }
            }
        }
    } else {
        $texto = "MIL"; // Fallback simple para pruebas
    }

    return "SON: " . $texto . " CON " . $decimal . "/100 SOLES";
}

try {
    $stmt = $db->prepare("
        SELECT v.*, v.created_at as fecha_emision, f.fecha as fecha_funcion, h.hora, p.nombre as pelicula, s.nombre as sala, l.nombre as cine, l.direccion
        FROM tbl_ventas v
        JOIN tbl_funciones f ON v.id_funcion = f.id
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_locales l ON s.local = l.id
        LEFT JOIN tbl_hora h ON f.id_hora = h.id
        WHERE v.codigo = ?
    ");

    $stmt->execute([$codigo]);
    $venta = $stmt->fetch();

    if (!$venta) die("Ticket no encontrado");

    // Asientos
    $stmtBoletos = $db->prepare("SELECT * FROM tbl_boletos WHERE id_venta = ?");
    $stmtBoletos->execute([$venta['id']]);
    $boletos = $stmtBoletos->fetchAll();

    // Concatenate seat numbers
    $asientosStr = [];
    foreach ($boletos as $b) {
        $asientosStr[] = $b['fila'] . $b['numero'];
    }
    $asientosLine = implode(',', $asientosStr);

    // Calculations
    $total = $venta['total'];
    $subtotal = $total / 1.18;
    $igv = $total - $subtotal;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket <?php echo $venta['codigo']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            /* Monospace is key for thermal receipts */
            font-size: 11px;
            /* Slightly smaller for density */
            background-color: #eee;
            margin: 0;
            padding: 20px;
            color: #000;
        }

        .ticket {
            width: 72mm;
            /* Typical printable area for 80mm paper */
            background: #fff;
            margin: 0 auto;
            padding: 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .header {
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .legal-info {
            font-size: 10px;
        }

        .receipt-title {
            margin-top: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .receipt-number {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .line {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }

        .solid-line {
            border-bottom: 1px solid #000;
            margin: 5px 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 80px auto;
            gap: 2px;
            text-align: left;
            margin: 5px 0;
        }

        .info-label {
            font-weight: bold;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .table th {
            text-align: left;
            border-bottom: 1px solid #000;
            font-size: 10px;
            padding-bottom: 2px;
        }

        .table td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .totals-table {
            width: 100%;
            margin-top: 5px;
            font-size: 11px;
        }

        .totals-table td {
            padding: 1px 0;
        }

        .footer {
            margin-top: 15px;
            font-size: 9px;
            text-align: center;
        }

        .hash {
            font-size: 8px;
            overflow-wrap: break-word;
            margin: 5px 0;
            word-break: break-all;
        }

        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }

            .ticket {
                box-shadow: none;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .btn-container {
                display: none;
            }

            @page {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <div class="ticket">
        <!-- Header -->
        <div class="header text-center">
            <div class="company-name">CINERAMA</div>
            <div class="legal-info">PENTARAMA S.A.</div>
            <div class="legal-info">RUC: 20510360932</div>
            <div class="legal-info">DOM FISCAL: AV ARGENTINA 3093 CC MINKA</div>
            <div class="legal-info">CALLAO - CALLAO - PROV. CONST. DEL CALLAO</div>
        </div>

        <div class="solid-line"></div>

        <!-- Receipt Info -->
        <div class="text-center">
            <div class="receipt-title">BOLETA DE VENTA ELECTRONICA</div>
            <div class="receipt-number"><?php echo $venta['codigo']; ?></div>
        </div>

        <div class="info-grid">
            <div class="info-label">FECHA EMISIÓN:</div>
            <div><?php echo date('d/m/Y', strtotime($venta['fecha_emision'])); ?></div>

            <div class="info-label">HORA EMISIÓN:</div>
            <div><?php echo date('H:i:s', strtotime($venta['fecha_emision'])); ?></div>

            <div class="info-label">CAJERO:</div>
            <div>WEB-AUTO</div>
        </div>

        <div class="line"></div>

        <div class="info-grid">
            <div class="info-label">CLIENTE:</div>
            <div><?php echo strtoupper($venta['cliente_nombre']); ?></div>

            <div class="info-label"> <?php echo (strlen($venta['cliente_doc']) == 11) ? 'RUC' : 'DNI'; ?>:</div>
            <div><?php echo $venta['cliente_doc']; ?></div>

            <div class="info-label">DIRECCIÓN:</div>
            <div>-</div>
        </div>

        <div class="line"></div>

        <!-- Movie Info -->
        <div style="font-size: 13px; font-weight: bold;">
            <div class="text-left" style="margin-bottom: 2px;">PELÍCULA:</div>
            <div class="text-left" style="margin-bottom: 5px; font-size: 16px;"><?php echo strtoupper($venta['pelicula']); ?></div>

            <div class="info-grid" style="grid-template-columns: 1fr 1fr;">
                <div>SALA: <?php echo strtoupper($venta['sala']); ?></div>
                <div class="text-right">FECHA: <?php echo date('d/m/Y', strtotime($venta['fecha_funcion'])); ?></div>

                <div>HORA: <?php echo date('h:i A', strtotime($venta['hora'])); ?></div>
                <div class="text-right" style="margin-top: 8px; font-weight: 900;">ASIENTOS:</div>
                <div class="text-right" style="grid-column: 2;"><?php echo $asientosLine; ?></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 10px;">CANT.</th>
                    <th>DESCRIPCIÓN</th>
                    <th class="text-right">P.UNIT</th>
                    <th class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boletos as $b): ?>
                    <tr>
                        <td>1</td>
                        <td>ENTRADA GENERAL</td>
                        <td class="text-right"><?php echo number_format($b['precio'], 2); ?></td>
                        <td class="text-right"><?php echo number_format($b['precio'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="line"></div>

        <table class="totals-table">
            <tr>
                <td class="text-right">OP. GRAVADA:</td>
                <td class="text-right" style="width: 60px;">S/ <?php echo number_format($subtotal, 2); ?></td>
            </tr>
            <tr>
                <td class="text-right">IGV (18%):</td>
                <td class="text-right">S/ <?php echo number_format($igv, 2); ?></td>
            </tr>
            <tr>
                <td class="text-right">OP. EXONERADA:</td>
                <td class="text-right">S/ 0.00</td>
            </tr>
            <tr>
                <td class="text-right" style="padding-top: 5px;"><span class="bold" style="font-size: 12px;">IMPORTE TOTAL:</span></td>
                <td class="text-right" style="padding-top: 5px;"><span class="bold" style="font-size: 12px;">S/ <?php echo number_format($total, 2); ?></span></td>
            </tr>
        </table>

        <div style="margin-top: 10px; font-size: 10px; text-transform: uppercase;">
            <?php echo numeroALetras($total); ?>
        </div>

        <div class="line"></div>

        <!-- Footer / Legal -->
        <div class="footer">
            <div>Representación Impresa de la <br> BOLETA DE VENTA ELECTRÓNICA</div>
            <div style="margin-top: 5px;">Autorizado mediante Resolución de Intendencia <br> N° 034-005-0005315</div>
            <div style="margin-top: 5px;">Consulte su documento en <br> <strong>www.cinerama.com.pe/facturacion</strong></div>

            <!-- Simulated Hash -->
            <div class="hash text-center" style="color: #666; margin-top: 10px;">
                Resumen: <?php echo substr(hash('sha256', $venta['codigo'] . $total), 0, 28) . '...'; ?>
            </div>
        </div>

        <div class="text-center" style="margin-top: 15px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo $venta['codigo']; ?>|<?php echo $total; ?>|<?php echo date('Y-m-d'); ?>|PEN" alt="QR">
        </div>

        <!-- Buttons -->
        <div class="btn-container" style="margin-top: 20px; text-align: center; border-top: 1px dotted #ccc; padding-top: 10px;">
            <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #333; color: #fff; border:none; border-radius: 3px;">Imprimir</button>
            <a href="index.php" style="padding: 8px 15px; text-decoration: none; color: #333; margin-left: 10px; font-size: 12px;">Volver al Inicio</a>
        </div>
    </div>

</body>

</html>