<?php
require_once '../../config/config.php';

$page_title = "Reporte de Bordereaux";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$id_distribuidora = isset($_GET['id_distribuidora']) ? (int)$_GET['id_distribuidora'] : 0;

// Obtener distribuidoras para el filtro
try {
    $stmtDist = $db->query("SELECT id, nombre FROM tbl_distribuidora ORDER BY nombre");
    $distribuidoras = $stmtDist->fetchAll();
} catch (PDOException $e) {
    $distribuidoras = [];
}

// Obtener datos del reporte
$tree = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "SELECT 
                    d.nombre as distribuidora_nombre,
                    p.nombre as pelicula_nombre,
                    s.nombre as sala_nombre,
                    s.capacidad as sala_capacidad,
                    DATE(f.fecha) as fecha_funcion,
                    TIME(h.hora) as hora_funcion,
                    f.id as funcion_id,
                    t.nombre as nombre_tarifa,
                    COUNT(b.id) as cantidad_boletos,
                    t.precio as precio_unitario,
                    SUM(b.precio) as total_bruto
                FROM tbl_boletos b
                JOIN tbl_ventas v ON b.id_venta = v.id
                JOIN tbl_funciones f ON v.id_funcion = f.id
                JOIN tbl_hora h ON f.id_hora = h.id
                JOIN tbl_sala s ON f.id_sala = s.id
                JOIN tbl_pelicula p ON f.id_pelicula = p.id
                JOIN tbl_distribuidora d ON p.distribuidora = d.id
                JOIN tbl_tarifa t ON b.id_tarifa = t.id
                WHERE v.estado = 'PAGADO' 
                AND b.estado = 'ACTIVO'
                AND DATE(f.fecha) BETWEEN ? AND ? ";

        $params = [$fecha_inicio, $fecha_fin];

        if ($id_distribuidora > 0) {
            $sql .= " AND d.id = ? ";
            $params[] = $id_distribuidora;
        }

        // Group by granular level to build tree
        $sql .= " GROUP BY d.nombre, p.nombre, s.nombre, f.id, t.nombre, t.precio
                  ORDER BY d.nombre, p.nombre, s.nombre, f.fecha, h.hora";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $raw_data = $stmt->fetchAll();

        // Build Tree Structure
        foreach ($raw_data as $row) {
            $dist = $row['distribuidora_nombre'];
            $movie = $row['pelicula_nombre'];
            $sala = $row['sala_nombre'];
            $func_id = $row['funcion_id'] . '_' . $row['hora_funcion'];

            if (!isset($tree[$dist])) $tree[$dist] = ['data' => [], 'totals' => ['can_nor' => 0, 'total_bruto' => 0, 'total_neto' => 0]];
            if (!isset($tree[$dist]['data'][$movie])) $tree[$dist]['data'][$movie] = ['data' => [], 'totals' => ['can_nor' => 0, 'total_bruto' => 0, 'total_neto' => 0]];

            if (!isset($tree[$dist]['data'][$movie]['data'][$sala])) {
                $tree[$dist]['data'][$movie]['data'][$sala] = [
                    'data' => [],
                    'totals' => ['can_nor' => 0, 'total_bruto' => 0, 'total_neto' => 0]
                ];
            }

            $precio_neto_unit = $row['precio_unitario'] / 1.18;
            $total_neto = $row['total_bruto'] / 1.18;

            if (!isset($tree[$dist]['data'][$movie]['data'][$sala]['data'][$func_id])) {
                $tree[$dist]['data'][$movie]['data'][$sala]['data'][$func_id] = [
                    'hora' => $row['hora_funcion'],
                    'capacidad' => $row['sala_capacidad'],
                    'can_nor' => 0,
                    'total_bruto' => 0,
                    'total_neto' => 0,
                    'pu_bruto' => $row['precio_unitario'],
                    'pu_neto' => $precio_neto_unit
                ];
            }

            $node = &$tree[$dist]['data'][$movie]['data'][$sala]['data'][$func_id];
            $node['can_nor'] += $row['cantidad_boletos'];
            $node['total_bruto'] += $row['total_bruto'];
            $node['total_neto'] += $total_neto;

            $tree[$dist]['data'][$movie]['data'][$sala]['totals']['can_nor'] += $row['cantidad_boletos'];
            $tree[$dist]['data'][$movie]['data'][$sala]['totals']['total_bruto'] += $row['total_bruto'];
            $tree[$dist]['data'][$movie]['data'][$sala]['totals']['total_neto'] += $total_neto;

            $tree[$dist]['data'][$movie]['totals']['can_nor'] += $row['cantidad_boletos'];
            $tree[$dist]['data'][$movie]['totals']['total_bruto'] += $row['total_bruto'];
            $tree[$dist]['data'][$movie]['totals']['total_neto'] += $total_neto;

            $tree[$dist]['totals']['can_nor'] += $row['cantidad_boletos'];
            $tree[$dist]['totals']['total_bruto'] += $row['total_bruto'];
            $tree[$dist]['totals']['total_neto'] += $total_neto;
        }
    } catch (PDOException $e) {
        $error = "Error al generar reporte: " . $e->getMessage();
    }
}
?>

<main class="admin-content" style="padding: 10px;">
    <!-- Layout Container -->
    <div class="layout-wrapper" style="display: flex; height: calc(100vh - 80px); gap: 10px;">

        <!-- Sidebar Filters -->
        <div class="card sidebar-filters" style="width: 280px; padding: 15px; height: 100%; overflow-y: auto; flex-shrink: 0;">
            <h5 style="border-bottom: 2px solid #ddd; padding-bottom: 5px; margin-bottom: 15px;">Nuevos-Bordereaux</h5>

            <form method="GET" action="">
                <div class="form-group">
                    <label style="font-weight: bold; font-size: 0.9em;">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?php echo $fecha_inicio; ?>">
                </div>

                <div class="form-group">
                    <label style="font-weight: bold; font-size: 0.9em;">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?php echo $fecha_fin; ?>">
                </div>

                <div class="form-group">
                    <label style="font-weight: bold; font-size: 0.9em;">Moneda</label>
                    <select class="form-control form-control-sm" disabled>
                        <option>SOLES</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="font-weight: bold; font-size: 0.9em;">Distribuidora</label>
                    <div style="display: flex; gap: 5px;">
                        <select name="id_distribuidora" class="form-control form-control-sm">
                            <option value="0">-- Todas --</option>
                            <?php foreach ($distribuidoras as $dist): ?>
                                <option value="<?php echo $dist['id']; ?>" <?php echo ($id_distribuidora == $dist['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dist['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm btn-block" style="margin-top: 10px;">
                    <i class="fas fa-sync"></i> Procesar
                </button>
                <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm btn-block" style="margin-top: 5px;">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </form>
        </div>

        <!-- Main Report Area -->
        <div class="card report-container" style="flex-grow: 1; padding: 0; overflow: hidden; display: flex; flex-direction: column;">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin: 10px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-scroll-area" style="flex-grow: 1; overflow: auto; padding: 20px;">

                <?php if (empty($tree)): ?>
                    <div class="alert alert-info">No hay datos para el rango seleccionado.</div>
                <?php else: ?>

                    <?php foreach ($tree as $distName => $distData): ?>
                        <?php foreach ($distData['data'] as $movieName => $movieData): ?>

                            <!-- SEPARATE PAGE PER MOVIE -->
                            <div class="report-page">
                                <!-- PAGE HEADER -->
                                <div class="page-header-print">
                                    <h3 style="margin: 0; text-transform: uppercase;"><?php echo htmlspecialchars($_SESSION['local_nombre'] ?? 'CINERAMA'); ?></h3>
                                    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px;">
                                        <span><strong>Fecha Impresión:</strong> <?php echo date('d/m/Y H:i'); ?></span>
                                        <span><strong>Reporte:</strong> BORDEREAUX DE TAQUILLA</span>
                                    </div>
                                    <div style="margin-bottom: 10px; font-size: 1.1em;">
                                        <div><strong>Distribuidora:</strong> <?php echo htmlspecialchars($distName); ?></div>
                                        <div><strong>Película:</strong> <?php echo htmlspecialchars($movieName); ?></div>
                                        <div><strong>Rango:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> - <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></div>
                                    </div>
                                </div>

                                <table class="table-legacy">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%">Detalle</th>
                                            <th>Can Nor</th>
                                            <th>Can Mov P</th>
                                            <th>Can Tot</th>
                                            <th>% Ocu</th>
                                            <th>P.U. Bruto</th>
                                            <th>P.U. Neto</th>
                                            <th>Total Bruto</th>
                                            <th>Total Neto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($movieData['data'] as $salaName => $salaData): ?>
                                            <!-- Sala Row -->
                                            <tr class="row-sala">
                                                <td colspan="9">
                                                    <span style="margin-left: 20px;">
                                                        <i class="fas fa-minus-square"></i> Sala : <?php echo htmlspecialchars($salaName); ?>
                                                    </span>
                                                </td>
                                            </tr>

                                            <?php foreach ($salaData['data'] as $funcData):
                                                $canTot = $funcData['can_nor'];
                                                $ocu = ($funcData['capacidad'] > 0) ? ($canTot / $funcData['capacidad']) * 100 : 0;
                                            ?>
                                                <!-- Function Row -->
                                                <tr class="row-function">
                                                    <td>
                                                        <span style="margin-left: 40px;">
                                                            <i class="far fa-clock"></i> Función : <?php echo date('H:i', strtotime($funcData['hora'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-right"><?php echo number_format($canTot, 0); ?></td>
                                                    <td class="text-right">0</td>
                                                    <td class="text-right"><?php echo number_format($canTot, 0); ?></td>
                                                    <td class="text-right"><?php echo number_format($ocu, 2); ?>%</td>
                                                    <td class="text-right"><?php echo number_format($funcData['pu_bruto'], 2); ?></td>
                                                    <td class="text-right"><?php echo number_format($funcData['pu_neto'], 2); ?></td>
                                                    <td class="text-right"><?php echo number_format($funcData['total_bruto'], 2); ?></td>
                                                    <td class="text-right"><?php echo number_format($funcData['total_neto'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>

                                            <!-- Sala Total -->
                                            <tr class="row-sala-total">
                                                <td class="text-right" style="padding-right: 10px;">TOTAL <?php echo htmlspecialchars($salaName); ?>:</td>
                                                <td class="text-right"><?php echo number_format($salaData['totals']['can_nor'], 0); ?></td>
                                                <td class="text-right">0</td>
                                                <td class="text-right"><?php echo number_format($salaData['totals']['can_nor'], 0); ?></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td class="text-right"><?php echo number_format($salaData['totals']['total_bruto'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($salaData['totals']['total_neto'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?> <!-- End Sala -->

                                        <!-- Movie Total -->
                                        <tr class="row-movie-total">
                                            <td class="text-right" style="padding-right: 10px;">TOTAL GENERAL PELÍCULA:</td>
                                            <td class="text-right"><?php echo number_format($movieData['totals']['can_nor'], 0); ?></td>
                                            <td class="text-right">0</td>
                                            <td class="text-right"><?php echo number_format($movieData['totals']['can_nor'], 0); ?></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td class="text-right"><?php echo number_format($movieData['totals']['total_bruto'], 2); ?></td>
                                            <td class="text-right"><?php echo number_format($movieData['totals']['total_neto'], 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br><br>
                            </div>
                            <!-- END PAGE -->

                        <?php endforeach; ?>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
    /* Reset & Base */
    .admin-content {
        background: #f0f2f5;
        height: calc(100vh - 60px);
        overflow: hidden;
    }

    /* Legacy Table Styles */
    .table-legacy {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 11px;
    }

    .table-legacy thead th {
        background: #e1e8f0;
        color: #000;
        font-weight: bold;
        text-align: center;
        border: 1px solid #a0a0a0;
        padding: 4px;
    }

    .table-legacy td {
        border: 1px solid #cfe2f3;
        padding: 3px 5px;
        color: #333;
    }

    /* Headers */
    .row-sala td {
        background-color: #eef3f8;
        font-weight: bold;
        font-style: italic;
    }

    .row-function td {
        background-color: #fff;
    }

    .row-sala-total td {
        background-color: #f2f2f2;
        font-weight: bold;
        border-top: 1px solid #ccc;
    }

    .row-movie-total td {
        background-color: #d0d0d0;
        font-weight: bold;
        border-top: 2px solid #538dd5;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    /* Print Overrides */
    @media print {
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        /* Hide UI */
        .admin-sidebar,
        .admin-header,
        .sidebar-filters,
        .content-header button {
            display: none !important;
        }

        /* Layout Reset */
        .admin-content {
            position: relative;
            height: auto;
            overflow: visible;
            background: white;
            padding: 0 !important;
            margin: 0 !important;
        }

        .layout-wrapper {
            display: block !important;
            height: auto !important;
        }

        .report-container {
            border: none;
            box-shadow: none;
            width: 100% !important;
            margin: 0 !important;
            display: block !important;
        }

        .table-scroll-area {
            overflow: visible !important;
            padding: 0 !important;
        }

        /* Page Break Logic */
        .report-page {
            page-break-after: always;
            display: block;
            width: 100%;
        }

        .report-page:last-child {
            page-break-after: avoid;
        }

        .table-legacy {
            font-size: 10pt;
            width: 100%;
        }

        /* Ensure headers print colors if enabled, but use simple borders otherwise */
        .table-legacy th,
        .row-movie-total td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<?php // No footer include to keep layout clean 
?>