<?php
require_once 'panel/config/config.php';

$date = '2025-12-11';
echo "Checking functions for date: $date\n";
echo "Specific Check:\n";

try {
    // 1. Check raw count
    $stmt = $db->prepare("SELECT COUNT(*) FROM tbl_funciones WHERE fecha = ?");
    $stmt->execute([$date]);
    echo "Total functions on $date: " . $stmt->fetchColumn() . "\n";

    // 2. Check detailed status
    $stmt = $db->prepare("
        SELECT f.id, f.fecha, h.hora as hora_inicio, f.estado, p.nombre as pelicula, f.id_pelicula
        FROM tbl_funciones f
        JOIN tbl_pelicula p ON f.id_pelicula = p.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.fecha = ?
    ");
    $stmt->execute([$date]);
    $funcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($funcs as $f) {
        echo "ID: {$f['id']} | Peli: {$f['pelicula']} (ID: {$f['id_pelicula']}) | Hora: {$f['hora_inicio']} | Estado: {$f['estado']}\n";
    }

    // 3. specific check on the query used in dashboard.php
    echo "\nDashboard Query Check:\n";
    $sql_dash = "
        SELECT DISTINCT p.nombre
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        WHERE p.estado = '1' 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
    ";
    echo "Query: $sql_dash\n";
    // Check what CURRENT_DATE evaluates to
    $stmtDate = $db->query("SELECT CURRENT_DATE");
    echo "DB CURRENT_DATE: " . $stmtDate->fetchColumn() . "\n";

    $stmtDash = $db->query($sql_dash);
    $results = $stmtDash->fetchAll(PDO::FETCH_COLUMN);
    echo "Movies found by Dashboard query: " . implode(", ", $results) . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
