<?php
require_once 'includes/front_config.php';

$id_funcion = 12;

echo "<h2>Debug Seat Map (Funcion ID: $id_funcion)</h2>";

try {
    // 1. Get Function & Sala Info
    $stmt = $db->prepare("
        SELECT f.id, f.id_sala, s.nombre as sala, s.filas, s.columnas 
        FROM tbl_funciones f
        JOIN tbl_sala s ON f.id_sala = s.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id_funcion]);
    $funcion = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Sala Info:</h3>";
    echo "<pre>";
    print_r($funcion);
    echo "</pre>";

    if ($funcion) {
        // 2. Get Seats
        $stmtSeats = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
        $stmtSeats->execute([$funcion['id_sala']]);
        $seats = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Seats (First 10):</h3>";
        echo "Count: " . count($seats) . "<br>";
        echo "<pre>";
        print_r(array_slice($seats, 0, 10));
        echo "</pre>";

        // Check for 'Pasillo' or coordinates
        echo "<h3>Coordinate Check:</h3>";
        foreach ($seats as $s) {
            echo "Row: " . $s['fila'] . " | Col: " . $s['columna'] . " | Type: " . $s['tipo'] . "<br>";
            if ($s['num_asiento'] > 5) break;
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
