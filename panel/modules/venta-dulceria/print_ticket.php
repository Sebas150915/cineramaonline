<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Check permissions
if (!isset($_SESSION['user_id'])) {
    die("Acceso no autorizado");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("ID de venta inválido");
}

// Fetch Sale Header
$stmt = $db->prepare("SELECT v.*, u.usuario FROM tbl_ventas_dulceria v 
                      JOIN tbl_usuarios u ON v.id_usuario = u.id 
                      WHERE v.id = ?");
$stmt->execute([$id]);
$venta = $stmt->fetch();

if (!$venta) {
    die("Venta no encontrada");
}

// Fetch Details
$stmtDet = $db->prepare("SELECT d.*, p.nombre 
                         FROM tbl_ventas_dulceria_detalle d
                         JOIN tbl_productos p ON d.id_producto = p.id
                         WHERE d.id_venta = ?");
$stmtDet->execute([$id]);
$detalles = $stmtDet->fetchAll();

// Company Info (Hardcoded or from Config)
$empresa = [
    'ruc' => '20123456789', // Replace with real one if known or add config
    'nombre' => 'CINERAMA S.A.C.',
    'direccion' => 'Av. Principal 123, Ciudad'
];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $venta['id']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 80mm;
            /* Standard thermal paper width */
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 10px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <h2><?php echo $empresa['nombre']; ?></h2>
        <p>RUC: <?php echo $empresa['ruc']; ?><br>
            <?php echo $empresa['direccion']; ?></p>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></span>
    </div>
    <div class="info-row">
        <span>Ticket: <?php echo $venta['serie'] . '-' . str_pad($venta['correlativo'], 8, '0', STR_PAD_LEFT); ?></span>
    </div>
    <div class="info-row">
        <span>Cajero: <?php echo strtoupper($venta['usuario']); ?></span>
    </div>
    <div class="info-row">
        <span>Cliente: <?php echo strtoupper($venta['cliente_nombre']); ?></span>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Cant</th>
                <th style="width: 60%;">Desc</th>
                <th style="width: 30%;" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $item): ?>
                <tr>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td><?php echo strtoupper($item['nombre']); ?></td>
                    <td class="text-right"><?php echo number_format($item['importe'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals">
        <?php
        // Simple tax calculation derived from total involved (assuming prices include IGV)
        $total = (float)$venta['total'];
        $subtotal = $total / 1.18;
        $igv = $total - $subtotal;
        ?>
        <div class="info-row">
            <span>OP. GRAVADA:</span>
            <span>S/ <?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="info-row">
            <span>I.G.V. (18%):</span>
            <span>S/ <?php echo number_format($igv, 2); ?></span>
        </div>
        <div class="info-row" style="font-size: 14px; font-weight: bold; margin-top: 5px;">
            <span>TOTAL A PAGAR:</span>
            <span>S/ <?php echo number_format($total, 2); ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Gracias por su preferencia<br>
            Conserve este comprobante</p>
    </div>

</body>

</html>