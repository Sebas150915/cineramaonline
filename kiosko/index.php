<?php
require_once '../panel/config/config2.php';

$page_title = "Cartelera General";

// Obtener películas que tengan funciones activas con ratings y géneros
try {
    $stmt = $db->prepare("
        SELECT DISTINCT p.*, c.codigo as censura_codigo, g.nombre as genero_nombre
        FROM tbl_pelicula p
        JOIN tbl_funciones f ON p.id = f.id_pelicula
        LEFT JOIN tbl_censura c ON p.censura = c.id
        LEFT JOIN tbl_genero g ON p.genero = g.id
        WHERE p.estado = '1' 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
        ORDER BY p.fecha_estreno DESC, p.nombre ASC
    ");
    $stmt->execute();
    $peliculas = $stmt->fetchAll();
} catch (PDOException $e) {
    $peliculas = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kiosko Cinerama</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- App Styles -->
    <link rel="stylesheet" href="assets/css/kiosk.css">
</head>

<body>

    <!-- Header -->
    <header class="kiosk-header glass-panel" style="border-radius: 0 0 16px 16px; border-top: none;">
        <div class="brand-logo">CINERAMA</div>
        <div class="clock" style="font-weight: 600; font-size: 1.2rem;">
            <?php echo date('g:i A'); ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="kiosk-main">
        <div class="section-title">En Cartelera</div>

        <div class="movie-grid">
            <?php if (empty($peliculas)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                    <h2>No hay funciones disponibles por el momento.</h2>
                    <p style="color: var(--text-muted)">Intente más tarde.</p>
                </div>
            <?php else: ?>
                <?php foreach ($peliculas as $peli): ?>
                    <!-- Card con enlace a detalles/horarios (placeholder por ahora) -->
                    <div class="movie-card" onclick="window.location.href='cartelera.php?id=<?php echo $peli['id']; ?>'">
                        <img src="../uploads/peliculas/<?php echo htmlspecialchars($peli['img']); ?>"
                            alt="<?php echo htmlspecialchars($peli['nombre']); ?>"
                            class="movie-poster"
                            onerror="this.src='../assets/img/no-poster.jpg'"> <!-- Fallback image -->

                        <div class="movie-overlay">
                            <h3 class="movie-title"><?php echo htmlspecialchars($peli['nombre']); ?></h3>
                            <div class="movie-meta">
                                <span><?php echo $peli['duracion']; ?> min</span>
                                <span>|</span>
                                <span><?php echo htmlspecialchars($peli['genero']); ?></span>
                            </div>
                            <button class="btn-buy">Ver Horarios</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Navigation -->
    <nav class="kiosk-nav">
        <a href="index.php" class="nav-item active">
            <i class="fas fa-film nav-icon"></i>
            <span>Películas</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-popcorn nav-icon"></i>
            <span>Confitería</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-search nav-icon"></i>
            <span>Buscar</span>
        </a>
    </nav>

    <script src="assets/js/kiosk.js"></script>
</body>

</html>