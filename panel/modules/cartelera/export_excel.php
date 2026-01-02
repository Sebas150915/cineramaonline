<?php
require_once '../../config/config.php';

// Nombre del archivo
$filename = "Programacion_Semanal_" . date('Y-m-d') . ".xls";

// Limpiar buffer
if (ob_get_level()) ob_end_clean();

// Headers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // BOM for UTF-8

// 1. Obtener todas las carteleras ACTIVAS con todas las relaciones y los 6 horarios
try {
    $sql = "SELECT 
                c.id,
                l.nombre as cine, 
                l.id as cine_id,
                s.id as sala_id,
                s.nombre as sala_nombre,
                s.capacidad as sala_capacidad,
                p.nombre as pelicula,
                d.nombre as distribuidora,
                g.nombre as genero,
                p.duracion,
                c.fecha_inicio,
                c.fecha_fin,
                h1.hora as hora_f1,
                h2.hora as hora_f2,
                h3.hora as hora_f3,
                h4.hora as hora_f4,
                h5.hora as hora_f5,
                h6.hora as hora_f6
            FROM tbl_cartelera c 
            JOIN tbl_locales l ON c.local = l.id 
            LEFT JOIN tbl_sala s ON c.sala = s.id 
            JOIN tbl_pelicula p ON c.pelicula = p.id 
            LEFT JOIN tbl_distribuidora d ON p.distribuidora = d.id
            LEFT JOIN tbl_genero g ON p.genero = g.id
            LEFT JOIN tbl_hora h1 ON c.id_hora_f1 = h1.id
            LEFT JOIN tbl_hora h2 ON c.id_hora_f2 = h2.id
            LEFT JOIN tbl_hora h3 ON c.id_hora_f3 = h3.id
            LEFT JOIN tbl_hora h4 ON c.id_hora_f4 = h4.id
            LEFT JOIN tbl_hora h5 ON c.id_hora_f5 = h5.id
            LEFT JOIN tbl_hora h6 ON c.id_hora_f6 = h6.id
            WHERE c.estado = '1'
            ORDER BY l.orden ASC, l.nombre ASC, s.nombre ASC, c.fecha_inicio ASC";

    $stmt = $db->query($sql);
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

// 2. Procesar datos para agrupar
$data = [];
$min_date = null;
$max_date = null;

if (!empty($raw_data)) {
    // Inicializar min/max con el primero
    $min_date = strtotime($raw_data[0]['fecha_inicio']);
    $max_date = strtotime($raw_data[0]['fecha_fin']);

    foreach ($raw_data as $row) {
        $cine = $row['cine'];
        $sala = $row['sala_nombre'] ? $row['sala_nombre'] : 'SIN SALA';
        $sala_cap = $row['sala_capacidad'] ? $row['sala_capacidad'] : 0;

        // Actualizar rango de fechas global
        $start = strtotime($row['fecha_inicio']);
        $end = strtotime($row['fecha_fin']);
        if ($start < $min_date) $min_date = $start;
        if ($end > $max_date) $max_date = $end;

        // Formatear horas (MySQL TIME 'HH:MM:SS' -> 'g:i')
        $times = [];
        for ($i = 1; $i <= 6; $i++) {
            $h = $row["hora_f$i"];
            $times[] = $h ? date('g:i', strtotime($h)) : '';
        }

        $row['f_times'] = $times;

        // Grouping
        $data[$cine][$sala]['capacidad'] = $sala_cap;
        $data[$cine][$sala]['peliculas'][] = $row;
    }
}

// Formato de fecha para el título
setlocale(LC_TIME, 'es_ES.UTF-8', 'spanish');
$date_title = "PROGRAMACION";
if ($min_date && $max_date) {
    $d_inicio = date('d', $min_date);
    $d_fin = date('d', $max_date);
    $mes = strtoupper(strftime('%B', $max_date));
    $anio = date('Y', $max_date);
    $date_title .= " SEMANA DEL $d_inicio AL $d_fin DE $mes $anio";
}
?>
<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .cine-header {
            background-color: #ffffff;
            font-weight: bold;
            font-size: 14px;
            text-align: left;
            border: none;
            padding-top: 15px;
        }

        .table-header {
            background-color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
        }

        .text-left {
            text-align: left;
        }

        .main-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            border: none;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <table>
        <tr>
            <td colspan="12" class="main-title"><?php echo $date_title; ?></td>
        </tr>

        <?php foreach ($data as $cine_nombre => $salas): ?>
            <!-- Encabezado Cine -->
            <tr>
                <td colspan="12" class="cine-header"><?php echo htmlspecialchars($cine_nombre); ?></td>
            </tr>

            <!-- Encabezados Tabla -->
            <tr class="table-header">
                <td width="50">SALA</td>
                <td width="50">CAP</td>
                <td width="300">PELICULA</td>
                <td width="100">DISTRIB</td>
                <td width="100">GENERO</td>
                <td width="60">DURACI.</td>
                <td width="60">F1</td>
                <td width="60">F2</td>
                <td width="60">F3</td>
                <td width="60">F4</td>
                <td width="60">F5</td>
                <td width="60">F6</td>
            </tr>

            <?php foreach ($salas as $sala_nombre => $sala_data): ?>
                <?php
                $cap = $sala_data['capacidad'];
                $peliculas = $sala_data['peliculas'];
                $first = true;

                foreach ($peliculas as $peli):
                ?>
                    <tr>
                        <!-- Sala y Capacidad solo en la primera fila de la sala -->
                        <td><?php echo $first ? htmlspecialchars(str_replace('SALA ', '', $sala_nombre)) : ''; ?></td>
                        <td><?php echo $first ? $cap : ''; ?></td>

                        <td class="text-left"><?php echo htmlspecialchars($peli['pelicula']); ?></td>
                        <td><?php echo htmlspecialchars($peli['distribuidora']); ?></td>
                        <td><?php echo htmlspecialchars($peli['genero']); ?></td>
                        <td><?php echo substr($peli['duracion'], 0, 5); ?></td>

                        <!-- Horarios F1 - F6 -->
                        <?php
                        foreach ($peli['f_times'] as $time) {
                            echo "<td>$time</td>";
                        }
                        ?>
                    </tr>
                <?php
                    $first = false;
                endforeach;
                ?>
            <?php endforeach; ?>

            <!-- Fila vacía separadora -->
            <tr>
                <td colspan="12" style="border:none; height: 10px;"></td>
            </tr>

        <?php endforeach; ?>

    </table>

</body>

</html>