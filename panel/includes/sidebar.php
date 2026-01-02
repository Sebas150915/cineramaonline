<?php
$role = $_SESSION['rol'] ?? 'guest';
// Normalize legacy 'admin' to superadmin for checks if needed, but 'admin' maps to superadmin logic usually
$isSuper = ($role === 'superadmin' || $role === 'admin');
$isSupervisor = ($isSuper || $role === 'supervisor');
// Ventas only sees Dashboard + Reports, maybe Communication?
// Let's assume Ventas is for reporting/viewing.
?>
<!-- Sidebar -->
<nav class="admin-sidebar">
    <!-- Gestión de Contenido -->
    <?php if ($isSupervisor): ?>
        <div class="sidebar-section">
            <div class="sidebar-title">Gestión de Contenido</div>
            <ul class="sidebar-menu">
                <li class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/peliculas/">
                        <i class="fas fa-video"></i>
                        <span>Películas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/cartelera/">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Cartelera</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/funciones/">
                        <i class="fas fa-film"></i>
                        <span>Funciones</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/horarios/">
                        <i class="fas fa-clock"></i>
                        <span>Horarios</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/productos/">
                        <i class="fas fa-candy-cane"></i>
                        <span>Dulcería</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php else: ?>
        <div class="sidebar-section">
            <ul class="sidebar-menu">
                <li class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- POS / Ventas Section (Visible for anyone with permissions) -->
    <?php if (isset($_SESSION['permiso_boleteria']) && $_SESSION['permiso_boleteria']): ?>
        <div class="sidebar-section">
            <div class="sidebar-title">Boletería</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>../pos/" target="_blank">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Venta Entradas</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['permiso_dulceria']) && $_SESSION['permiso_dulceria']): ?>
        <div class="sidebar-section">
            <div class="sidebar-title">Dulcería</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/venta-dulceria/">
                        <i class="fas fa-candy-cane"></i>
                        <span>Venta Dulcería</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Configuración -->
    <?php if ($isSupervisor): ?>
        <div class="sidebar-section">
            <div class="sidebar-title">Configuración</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/censuras/">
                        <i class="fas fa-ban"></i>
                        <span>Censuras</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/generos/">
                        <i class="fas fa-theater-masks"></i>
                        <span>Géneros</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/distribuidoras/">
                        <i class="fas fa-building"></i>
                        <span>Distribuidoras</span>
                    </a>
                </li>
                <?php if ($isSuper): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>modules/cines/">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Locales</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>modules/usuarios/">
                            <i class="fas fa-user-shield"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/tarifas/">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Tarifas</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/slider/">
                        <i class="fas fa-images"></i>
                        <span>Slider Home</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Comunicación -->
    <?php if ($isSupervisor): ?>
        <div class="sidebar-section">
            <div class="sidebar-title">Comunicación</div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/contactos/">
                        <i class="fas fa-envelope"></i>
                        <span>Mensajes</span>
                        <span class="notification-badge">8</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/promociones/">
                        <i class="fas fa-tag"></i>
                        <span>Promociones</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo BASE_URL; ?>modules/noticias/">
                        <i class="fas fa-newspaper"></i>
                        <span>Noticias</span>
                    </a>
                </li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Reportes -->
    <div class="sidebar-section">
        <div class="sidebar-title">Reportes</div>
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>modules/ventas/">
                    <i class="fas fa-chart-bar"></i>
                    <span>Ventas</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>modules/reportes/bordereaux.php">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Bordereaux</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>modules/asistencia/">
                    <i class="fas fa-users"></i>
                    <span>Asistencia</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo BASE_URL; ?>modules/inventario/">
                    <i class="fas fa-boxes"></i>
                    <span>Inventario</span>
                </a>
            </li>
        </ul>
    </div>
</nav>