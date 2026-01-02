<?php
require_once 'panel/config/config.php';

// Mock Session (After config/session_start)
$_SESSION['pos_id_local'] = 2; // Default to Pacifico

$id_local = $_SESSION['pos_id_local'];
$today = date('Y-m-d'); // 2025-12-11

echo "Mocking API for ID: $id_local / Date: $today\n";
echo "-------------------------------------------\n";

// Copy-Paste of the logic from get_movies.php
try {
    $stmt = $db->prepare("
SELECT DISTINCT p.id, p.nombre, p.img, p.censura
FROM tbl_pelicula p
JOIN tbl_funciones f ON p.id = f.id_pelicula
JOIN tbl_sala s ON f.id_sala = s.id
WHERE s.local = ?
AND p.estado = '1'
AND f.estado = '1'
AND f.fecha >= ?
ORDER BY p.nombre ASC
");
    $stmt->execute([$id_local, $today]);
    $peliculas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($peliculas) . " movies.\n";

    // 2. For each movie, get showtimes grouped by date
    foreach ($peliculas as &$p) {
        // Updated logic with proper JOIN and WHERE
        $stmtF = $db->prepare("
SELECT f.id, f.fecha, h.hora, s.nombre as sala
FROM tbl_funciones f
JOIN tbl_hora h ON f.id_hora = h.id
JOIN tbl_sala s ON f.id_sala = s.id
WHERE f.id_pelicula = ?
AND s.local = ?
AND f.estado = '1'
AND f.fecha >= ?
ORDER BY f.fecha ASC, h.hora ASC
");
        $stmtF->execute([$p['id'], $id_local, $today]);
        $funcs = $stmtF->fetchAll(PDO::FETCH_ASSOC);

        $p['funciones'] = [];
        foreach ($funcs as $f) {
            $p['funciones'][$f['fecha']][] = $f;
        }
    }

    echo "JSON Output:\n";
    echo json_encode(['debug_local' => $id_local, 'data' => $peliculas], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
