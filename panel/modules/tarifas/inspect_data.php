<?php
require_once '../../config/config.php';
try {
    echo "Locales:\n";
    $locales = $db->query("SELECT * FROM tbl_locales")->fetchAll(PDO::FETCH_ASSOC);
    print_r($locales);

    echo "\nTarifas existentes:\n";
    $tarifas = $db->query("SELECT * FROM tbl_tarifa")->fetchAll(PDO::FETCH_ASSOC);
    print_r($tarifas);
} catch (PDOException $e) {
    echo $e->getMessage();
}
