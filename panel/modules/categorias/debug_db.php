<?php
require_once '../../config/config.php';

echo "<h2>Debug DB: tbl_categoria</h2>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tbl_categoria'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'tbl_categoria' exists.<br>";

        // Show columns
        $stmt = $db->query("DESCRIBE tbl_categoria");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";

        // Try manual insert
        echo "Attempting manual insert...<br>";
        $testName = "Test Category " . time();
        $stmt = $db->prepare("INSERT INTO tbl_categoria (nombre, estado) VALUES (?, ?)");
        if ($stmt->execute([$testName, '1'])) {
            echo "Manual insert successful. ID: " . $db->lastInsertId() . "<br>";
        } else {
            echo "Manual insert failed.<br>";
            print_r($stmt->errorInfo());
        }
    } else {
        echo "Table 'tbl_categoria' DOES NOT EXIST.<br>";
        // Attempt to create it again
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_categoria` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `nombre` varchar(50) NOT NULL,
          `estado` char(1) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->exec($sql);
        echo "Attempted to create table.<br>";
    }
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
}
