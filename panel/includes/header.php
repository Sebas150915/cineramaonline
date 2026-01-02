<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Cinerama Admin</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">

    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>

<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="logo-section">
            <button class="header-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">CINERAMA<span>ADMIN</span></div>
        </div>
        <div class="user-panel">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></div>
                <div class="user-role">
                    <?php
                    echo ucfirst($_SESSION['rol'] ?? 'Invitado');
                    if (!empty($_SESSION['local_nombre'])) {
                        echo ' - ' . htmlspecialchars($_SESSION['local_nombre']);
                    }
                    ?>
                </div>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn-logout" title="Cerrar Sesión" style="margin-left: 15px; color: white; text-decoration: none;">
                <i class="fas fa-sign-out-alt fa-lg"></i>
            </a>
        </div>
    </header>

    <!-- Container Principal -->
    <div class="admin-container">
        <div class="admin-sidebar-overlay"></div>