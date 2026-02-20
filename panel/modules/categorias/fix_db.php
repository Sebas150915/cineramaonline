<?php
require_once '../../config/config.php';

echo "<h2>Fixing DB: tbl_categoria</h2>";

try {
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tbl_categoria'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'tbl_categoria' exists. Modifying ID column...<br>";

        // Modify column to include AUTO_INCREMENT
        // Note: For MySQL/MariaDB
        $sql = "ALTER TABLE `tbl_categoria` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
        $db->exec($sql);
        echo "Executed ALTER TABLE...<br>";

        // Check again
        $stmt = $db->query("DESCRIBE tbl_categoria");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";

        // Try manual insert again
        echo "Retrying manual insert...<br>";
        $testName = "Categoria Test " . time();
        $stmt = $db->prepare("INSERT INTO tbl_categoria (nombre, estado) VALUES (?, ?)");
        if ($stmt->execute([$testName, '1'])) {
            echo "Manual insert successful. ID: " . $db->lastInsertId() . "<br>";
        } else {
            echo "Manual insert failed.<br>";
            print_r($stmt->errorInfo());
        }
    } else {
        echo "Table 'tbl_categoria' DOES NOT EXIST.<br>";
    }
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
}
