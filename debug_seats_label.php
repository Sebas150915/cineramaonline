<?php
require_once 'includes/front_config.php';
try {
    $seats = $db->query("SELECT * FROM tbl_sala_asiento WHERE idsala=16 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    print_r($seats);
} catch (PDOException $e) {
    echo $e->getMessage();
}
