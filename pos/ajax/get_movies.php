<?php
require_once '../../panel/config/config.php';
require_once '../includes/auth_check.php';

$id_local = $_SESSION['pos_id_local'];
$today = date('Y-m-d');

// Refresh ID Local from DB to ensure session is not stale
if (isset($_SESSION['pos_user_id'])) {
    $stmtUser = $db->prepare("SELECT id_local FROM tbl_usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['pos_user_id']]);
    $dbUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($dbUser && $dbUser['id_local'] != $id_local) {
        $id_local = $dbUser['id_local'];
        $_SESSION['pos_id_local'] = $id_local; // Update session
    }
}

// 1. Get Active Movies for this Local with Future Functions
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

    // 2. For each movie, get showtimes grouped by date
    foreach ($peliculas as &$p) {
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

    echo json_encode(['debug_local' => $id_local, 'data' => $peliculas]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage(), 'debug_local' => $id_local]);
}
