<?php
require_once '../../panel/config/config.php';
require_once '../includes/auth_check.php';

$id_funcion = $_GET['id'] ?? 0;

if (!$id_funcion) {
    echo "Error: ID Función inválido";
    exit;
}

// Get Function Info
$stmt = $db->prepare("
    SELECT f.*, p.nombre as pelicula, s.nombre as sala, s.columnas, l.nombre as cine, s.local as id_local
    FROM tbl_funciones f
    JOIN tbl_pelicula p ON f.id_pelicula = p.id
    JOIN tbl_sala s ON f.id_sala = s.id
    JOIN tbl_locales l ON s.local = l.id
    WHERE f.id = ?
");
$stmt->execute([$id_funcion]);
$funcion = $stmt->fetch();

// Get Occupied Seats
$stmtO = $db->prepare("
    SELECT b.id_asiento 
    FROM tbl_boletos b
    JOIN tbl_ventas v ON b.id_venta = v.id
    WHERE v.id_funcion = ? 
    AND v.estado IN ('PAGADO', 'PENDIENTE')
    AND b.estado = 'ACTIVO'
");
$stmtO->execute([$id_funcion]);
$ocupados = array_flip($stmtO->fetchAll(PDO::FETCH_COLUMN));

// Get Seats
$stmtS = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, num_asiento");
$stmtS->execute([$funcion['id_sala']]);
$asientos = $stmtS->fetchAll();

// Get Tariffs for this local
$stmtT = $db->prepare("SELECT id, nombre, precio FROM tbl_tarifa WHERE local = ? ORDER BY precio DESC");
$stmtT->execute([$funcion['id_local']]);
$tarifas = $stmtT->fetchAll(PDO::FETCH_ASSOC);

$response = [
    'info' => [
        'pelicula' => $funcion['pelicula'],
        'sala' => $funcion['sala'],
        'columns' => $funcion['columnas']
    ],
    'seats' => [],
    'tarifas' => $tarifas
];

foreach ($asientos as $a) {
    // Determine Row Index (rx)
    // Map 'A' -> 1, 'B' -> 2, etc. purely for grid positioning if needed
    // But keeping it numeric or mapped is safer.
    // Let's rely on numeric 'fila' if it's numeric, or convert letter.
    // However, compra_asientos logic (Lines 76-86) maps unique rows to 1..N.
    // Let's do a similar simple mapping here or just pass the raw data and let JS handle it?
    // Better to do it here for simplicity in JS.
}

// 1. Map Rows
$unique_rows = [];
foreach ($asientos as $a) {
    if (!in_array($a['fila'], $unique_rows)) {
        $unique_rows[] = $a['fila'];
    }
}
$unique_rows = array_values($unique_rows); // Re-index 0..N
$rowMap = array_flip($unique_rows); // 'A'=>0, 'B'=>1

// 2. Calculate Max Columns dynamically
$max_cols = 0;
foreach ($asientos as $a) {
    $col = (int)$a['columna']; // Assuming numerical column
    if ($col > $max_cols) $max_cols = $col;
}

// If DB has a value and it's reasonable, use it, else max found
$db_cols = (int)$funcion['columnas'];
if ($db_cols < $max_cols) $db_cols = $max_cols;
if ($db_cols == 0) $db_cols = 1;

$response['info']['max_cols'] = $db_cols;
$response['info']['rows'] = $unique_rows; // explicit rows for labels

foreach ($asientos as $a) {
    $rIndex = isset($rowMap[$a['fila']]) ? $rowMap[$a['fila']] + 1 : 1; // 1-based
    $cIndex = (int)$a['columna']; // 1-based usually

    // Safety for 0-indexed columns in DB? Usually they are 1..N
    if ($cIndex < 1) $cIndex = 1;

    $response['seats'][] = [
        'id' => $a['id'],
        'r' => $a['fila'],
        'c' => $a['num_asiento'], // Label
        't' => $a['tipo'],
        'o' => isset($ocupados[$a['id']]) ? 1 : 0,
        'rx' => $rIndex,
        'cx' => $cIndex
    ];
}

echo json_encode($response);
