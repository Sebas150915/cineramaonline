<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pos_user_id']) || !isset($_SESSION['pos_id_local'])) {
    session_destroy();
    header("Location: ../pos/index.php");
    exit;
}
