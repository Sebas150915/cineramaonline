<?php
function num_to_letras($number, $moneda = 'SOLES')
{
    $converted = '';
    $entero = floor($number);
    $decimal = round(($number - $entero) * 100);
    if ($decimal < 10) $decimal = "0" . $decimal;

    $letras = convertir_numero($entero);
    return strtoupper($letras) . " CON " . $decimal . "/100 " . $moneda;
}

function convertir_numero($n)
{
    if ($n == 0) return "CERO";

    $unidades = ["", "UNO", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE"];
    $dieciseis = ["", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE"];
    $decenas = ["", "DIEZ", "VEINTE", "TREINTA", "CUARENTA", "CINCUENTA", "SESENTA", "SETENTA", "OCHENTA", "NOVENTA"];
    $centenas = ["", "CIENTO", "DOSCIENTOS", "TRESCIENTOS", "CUATROCIENTOS", "QUINIENTOS", "SEISCIENTOS", "SETECIENTOS", "OCHOCIENTOS", "NOVECIENTOS"];

    $output = "";

    if ($n >= 1000000) {
        $m = floor($n / 1000000);
        $output .= ($m == 1 ? "UN MILLON" : convertir_numero($m) . " MILLONES") . " ";
        $n %= 1000000;
        if ($n == 0) return trim($output);
    }

    if ($n >= 1000) {
        $m = floor($n / 1000);
        $output .= ($m == 1 ? "MIL" : convertir_numero($m) . " MIL") . " ";
        $n %= 1000;
        if ($n == 0) return trim($output);
    }

    if ($n >= 100) {
        if ($n == 100) return trim($output . "CIEN");
        $m = floor($n / 100);
        $output .= $centenas[$m] . " ";
        $n %= 100;
    }

    if ($n >= 20) {
        $m = floor($n / 10); // 2..9
        $u = $n % 10;
        if ($n == 20) $output .= "VEINTE";
        elseif ($n < 30) $output .= "VEINTI" . $unidades[$u];
        else $output .= $decenas[$m] . ($u > 0 ? " Y " . $unidades[$u] : "");
        $n = 0;
    }

    if ($n > 0) {
        if ($n <= 9) $output .= $unidades[$n];
        elseif ($n >= 11 && $n <= 19) $output .= $dieciseis[$n - 10]; // 11=1..19=9
        elseif ($n == 10) $output .= "DIEZ";
    }

    return trim($output);
}
