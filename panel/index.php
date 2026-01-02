<?php
require_once './config/config.php';
require_once './includes/auth.php';

$page_title = "Dashboard";
include './includes/header.php';
include './includes/sidebar.php';
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Panel de Control</h1>
            <p class="page-subtitle">Bienvenido al sistema de administración de Cinerama Perú</p>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <?php
        // Obtener estadísticas
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM tbl_pelicula WHERE estado = '1'");
            $peliculas = $stmt->fetch()['total'];

            $stmt = $db->query("SELECT COUNT(*) as total FROM tbl_locales WHERE estado = '1'");
            $cines = $stmt->fetch()['total'];

            $stmt = $db->query("SELECT COUNT(*) as total FROM tbl_genero WHERE estado = '1'");
            $generos = $stmt->fetch()['total'];

            $stmt = $db->query("SELECT COUNT(*) as total FROM tbl_distribuidora WHERE estado = '1'");
            $distribuidoras = $stmt->fetch()['total'];
        } catch (PDOException $e) {
            $peliculas = $cines = $generos = $distribuidoras = 0;
        }
        ?>

        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h3 style="font-size: 36px; margin-bottom: 10px;"><?php echo $peliculas; ?></h3>
            <p style="opacity: 0.9;">Películas Activas</p>
            <i class="fas fa-video" style="position: absolute; right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
            <h3 style="font-size: 36px; margin-bottom: 10px;"><?php echo $cines; ?></h3>
            <p style="opacity: 0.9;">Cines Operativos</p>
            <i class="fas fa-film" style="position: absolute; right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
            <h3 style="font-size: 36px; margin-bottom: 10px;"><?php echo $generos; ?></h3>
            <p style="opacity: 0.9;">Géneros</p>
            <i class="fas fa-theater-masks" style="position: absolute; right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
        </div>

        <div class="card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
            <h3 style="font-size: 36px; margin-bottom: 10px;"><?php echo $distribuidoras; ?></h3>
            <p style="opacity: 0.9;">Distribuidoras</p>
            <i class="fas fa-building" style="position: absolute; right: 20px; top: 20px; font-size: 48px; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Accesos Rápidos</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="modules/generos/" class="btn btn-primary">
                <i class="fas fa-theater-masks"></i> Gestionar Géneros
            </a>
            <a href="modules/censuras/" class="btn btn-primary">
                <i class="fas fa-ban"></i> Gestionar Censuras
            </a>
            <a href="modules/distribuidoras/" class="btn btn-primary">
                <i class="fas fa-building"></i> Gestionar Distribuidoras
            </a>
            <a href="modules/peliculas/" class="btn btn-primary">
                <i class="fas fa-video"></i> Gestionar Películas
            </a>
            <a href="modules/cines/" class="btn btn-primary">
                <i class="fas fa-film"></i> Gestionar Cines
            </a>
        </div>
    </div>

    <!-- Últimas películas agregadas -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Últimas Películas Agregadas</h2>
        </div>
        <div class="table-container">
            <?php
            try {
                $stmt = $db->query("
                    SELECT p.*, g.nombre as genero_nombre, c.nombre as censura_nombre 
                    FROM tbl_pelicula p
                    LEFT JOIN tbl_genero g ON p.genero = g.id
                    LEFT JOIN tbl_censura c ON p.censura = c.id
                    ORDER BY p.id DESC
                    LIMIT 10
                ");
                $peliculas_recientes = $stmt->fetchAll();
            } catch (PDOException $e) {
                $peliculas_recientes = [];
            }
            ?>

            <?php if (count($peliculas_recientes) > 0): ?>
                <table class="datatable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Género</th>
                            <th>Censura</th>
                            <th>Duración</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peliculas_recientes as $pelicula): ?>
                            <tr>
                                <td><?php echo $pelicula['id']; ?></td>
                                <td><?php echo htmlspecialchars($pelicula['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($pelicula['genero_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($pelicula['censura_nombre']); ?></td>
                                <td><?php echo $pelicula['duracion']; ?></td>
                                <td>
                                    <?php if ($pelicula['estado'] == '1'): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">No hay películas registradas</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include './includes/footer.php'; ?>