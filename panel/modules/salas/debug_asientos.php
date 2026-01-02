<?php
require_once '../../config/config.php';

$sala_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sala_id <= 0) {
    die("ID de sala inválido");
}

echo "<h2>Debug: Asientos de la Sala $sala_id</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Fila</th><th>Columna</th><th>Num Asiento</th><th>Tipo</th><th>Estado</th></tr>";

try {
    $stmt = $db->prepare("SELECT * FROM tbl_sala_asiento WHERE idsala = ? ORDER BY fila, columna");
    $stmt->execute([$sala_id]);
    $asientos = $stmt->fetchAll();

    foreach ($asientos as $asiento) {
        echo "<tr>";
        echo "<td>{$asiento['id']}</td>";
        echo "<td>{$asiento['fila']}</td>";
        echo "<td>{$asiento['columna']}</td>";
        echo "<td>{$asiento['num_asiento']}</td>";
        echo "<td><strong>{$asiento['tipo']}</strong></td>";
        echo "<td>{$asiento['estado']}</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
}

echo "</table>";
echo "<br><a href='asientos.php?id=$sala_id'>Volver al mapa</a>";
