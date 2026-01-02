<?php
require_once 'panel/config/config.php';

try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "- " . $table . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
