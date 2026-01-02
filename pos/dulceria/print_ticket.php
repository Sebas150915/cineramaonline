<?php
define('IS_POS', true);
require_once '../../panel/config/config.php';
require_once '../includes/num_letras.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die("ID inválido");

$stmt = $db->prepare("SELECT v.*, u.usuario, 
                      e.razon_social, e.ruc, e.direccion as empresa_dir, 
                      l.direccion as local_dir, l.nombre as local_nombre
                      FROM tbl_ventas v 
                      JOIN tbl_usuarios u ON v.id_usuario = u.id 
                      LEFT JOIN tbl_locales l ON u.id_local = l.id
                      LEFT JOIN tbl_empresa e ON l.empresa = e.id
                      WHERE v.id = ?");
$stmt->execute([$id]);
$venta = $stmt->fetch();

if (!$venta) die("Venta no encontrada");

$stmtDet = $db->prepare("SELECT d.cantidad, d.importe, d.descripcion as nombre, d.precio_unitario
                         FROM tbl_ventas_det d
                         WHERE d.id_venta = ?");
$stmtDet->execute([$id]);
$detalles = $stmtDet->fetchAll();

// Calculations for SUNAT
$total = $venta['total'];
$igv_rate = 0.18;
$base_imponible = $total / (1 + $igv_rate);
$igv_amount = $total - $base_imponible;

// Company Info (Dynamic)
$empresa = [
    'razon_social' => $venta['razon_social'] ?? 'CINERAMA S.A.C.',
    'ruc' => $venta['ruc'] ?? '20000000000',
    'direccion' => $venta['empresa_dir'] ?? 'Dirección Fiscal No Definida',
    'local_dir' => $venta['local_dir'] ?? 'Dirección Local No Definida'
];

// Document Type
$doc_title = $venta['tipo_comprobante'] == 'FACTURA' ? 'FACTURA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA';
$serie_corr = $venta['serie'] . '-' . str_pad($venta['correlativo'], 8, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 5px;
            width: 80mm;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            padding: 2px 0;
        }

        .qty {
            width: 15%;
            text-align: center;
        }

        .desc {
            width: 55%;
        }

        .price {
            width: 30%;
            text-align: right;
        }

        .logo {
            max-width: 60%;
            margin-bottom: 5px;
        }
    </style>
</head>

<body onload="window.print();">
    <div class="text-center">
        <!-- <img src="../../assets/img/logo.png" class="logo" alt="Logo"> -->
        <div class="bold" style="font-size: 14px;"><?php echo $empresa['razon_social']; ?></div>
        <div>RUC: <?php echo $empresa['ruc']; ?></div>
        <div>Principal: <?php echo $empresa['direccion']; ?></div>
        <div>Sucursal: <?php echo $empresa['local_dir']; ?></div>
        <div class="divider"></div>
        <div class="bold" style="font-size: 13px;"><?php echo $doc_title; ?></div>
        <div class="bold"><?php echo $serie_corr; ?></div>
    </div>

    <div class="divider"></div>

    <div>Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['created_at'])); ?></div>
    <div>Cliente: <?php echo strtoupper($venta['cliente_nombre']); ?></div>
    <?php if ($venta['cliente_doc']): ?>
        <div>DOC: <?php echo $venta['cliente_doc']; ?></div>
    <?php endif; ?>
    <div>Cajero: <?php echo strtoupper($venta['usuario']); ?></div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th class="qty">CANT</th>
                <th class="desc">DESCRIPCION</th>
                <th class="price">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $d): ?>
                <tr>
                    <td class="qty"><?php echo $d['cantidad']; ?></td>
                    <td class="desc"><?php echo strtoupper($d['nombre']); ?></td>
                    <td class="price"><?php echo number_format($d['importe'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <table style="font-weight: bold;">
        <tr>
            <td class="text-right">OP. GRAVADA:</td>
            <td class="text-right">S/ <?php echo number_format($base_imponible, 2); ?></td>
        </tr>
        <tr>
            <td class="text-right">IGV (18%):</td>
            <td class="text-right">S/ <?php echo number_format($igv_amount, 2); ?></td>
        </tr>
        <tr>
            <td class="text-right" style="font-size: 14px;">TOTAL A PAGAR:</td>
            <td class="text-right" style="font-size: 14px;">S/ <?php echo number_format($total, 2); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <div>SON: <?php echo num_to_letras($total); ?></div>

    <div class="divider"></div>

    <div class="text-center">
        <?php
        // QR Content Standard: RUC|TIPO_DOC|SERIE|CORRELATIVO|IGV|TOTAL|FECHA|TIPO_DOC_CLI|NUM_DOC_CLI
        $qr_data = $empresa['ruc'] . '|' .
            ($venta['tipo_comprobante'] == 'FACTURA' ? '01' : '03') . '|' .
            $venta['serie'] . '|' .
            $venta['correlativo'] . '|' .
            number_format($igv_amount, 2, '.', '') . '|' .
            number_format($total, 2, '.', '') . '|' .
            date('d/m/Y', strtotime($venta['created_at'])) . '|' .
            '1' . '|' .
            ($venta['cliente_doc'] ? $venta['cliente_doc'] : '00000000');

        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($qr_data);
        ?>
        <img src="<?php echo $qr_url; ?>" style="width: 100px; height: 100px; margin: 10px 0;">
        <br>
        Representación impresa de la<br>
        <?php echo $doc_title; ?><br>
        Consulte su documento en<br>
        www.cinerama.com.pe
        <br><br>
        ¡GRACIAS POR SU PREFERENCIA!
    </div>
</body>

</html>