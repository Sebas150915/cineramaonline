<?php
session_start();
define('IS_POS', true);
require_once '../panel/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $db->prepare("SELECT * FROM tbl_usuarios WHERE usuario = ? AND estado = '1'");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Strict Role Check: ONLY 'cajero' allowed
            if ($user['rol'] !== 'cajero') {
                header("Location: index.php?error=2"); // Error 2: Acceso no autorizado para este rol
                exit;
            }

            $_SESSION['pos_user_id'] = $user['id'];
            $_SESSION['pos_user_nombre'] = $user['nombre'];
            $_SESSION['pos_user_usuario'] = $user['usuario'];
            $_SESSION['pos_user_rol'] = $user['rol'];
            $_SESSION['pos_id_local'] = $user['id_local'];

            // Store Permissions explicitly for POS logic
            $_SESSION['pos_permiso_boleteria'] = $user['permiso_boleteria'];
            $_SESSION['pos_permiso_dulceria'] = $user['permiso_dulceria'];

            // Routing Logic
            if ($user['permiso_boleteria']) {
                header("Location: dashboard.php");
            } elseif ($user['permiso_dulceria']) {
                header("Location: dulceria.php");
            } else {
                // No permissions
                session_destroy();
                header("Location: index.php?error=3"); // Error 3: No tiene permisos asignados
            }
            exit;
        } else {
            header("Location: index.php?error=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Error de base de datos");
    }
} else {
    header("Location: index.php");
    exit;
}
