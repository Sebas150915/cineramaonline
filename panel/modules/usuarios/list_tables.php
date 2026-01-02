<?php
require_once '../../config/config.php';
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
