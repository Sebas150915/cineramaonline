<?php
require_once 'includes/front_config.php';

echo "<h2>Debug Cartelera Query</h2>";
echo "Current Date (PHP): " . date('Y-m-d') . "<br>";

try {
    // 1. Check if column 'venta' exists or query fails
    $sql = "SELECT DISTINCT p.* 
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        WHERE p.estado = '1' 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
        ORDER BY p.venta DESC, p.fecha_estreno DESC";

    echo "<strong>Query:</strong> <pre>$sql</pre>";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $peliculas = $stmt->fetchAll();

    echo "<h3>Result:</h3>";
    echo "Count: " . count($peliculas) . "<br>";
    echo "<pre>";
    print_r($peliculas);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>SQL Error: " . $e->getMessage() . "</h3>";

    // Fallback: Test without ORDER BY
    echo "<h4>Retrying without ORDER BY...</h4>";
    try {
        $sql2 = "SELECT DISTINCT p.* 
            FROM tbl_pelicula p
            JOIN tbl_funciones f ON p.id = f.id_pelicula
            WHERE p.estado = '1' 
            AND f.estado = '1' 
            AND f.fecha >= CURRENT_DATE";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute();
        $res = $stmt2->fetchAll();
        echo "Count (simplified): " . count($res) . "<br>";
    } catch (PDOException $ex) {
        echo "<h3 style='color:red'>Simplified SQL Error: " . $ex->getMessage() . "</h3>";
    }
}
