<?php
// Ensure session is started (config.php handles this usually, but good to be safe)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pages that don't require authentication
$ignore_auth = ['login.php', 'logout.php', 'setup_roles.php'];
$current_script = basename($_SERVER['PHP_SELF']);

// Check if user is logged in
if (php_sapi_name() !== 'cli' && !defined('IS_POS') && !in_array($current_script, $ignore_auth) && !isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login page
    // We assume BASE_URL is defined in config.php
    if (defined('BASE_URL')) {
        header("Location: " . BASE_URL . "login.php");
    } else {
        // Fallback if config isn't loaded for some reason (shouldn't happen)
        header("Location: /cinerama/panel/login.php");
    }
    exit;
}

// Optional: Helper function to check roles
function hasRole($roles)
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['rol'], $roles);
}
