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
                    SELECT p.*, g.nombre as genero_nombre, c.nombre as censura_nombre, c.codigo as censura_codigo
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
                <table class="datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
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
                                <td>
                                    <?php if (!empty($pelicula['img'])): ?>
                                        <img src="<?php echo UPLOADS_URL . 'peliculas/' . $pelicula['img']; ?>"
                                            alt="Poster"
                                            style="width: 35px; height: 50px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <?php else: ?>
                                        <div style="width: 35px; height: 50px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-film" style="color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($pelicula['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pelicula['genero_nombre']); ?></td>
                                <td>
                                    <?php if (!empty($pelicula['censura_codigo'])): ?>
                                        <span style="background: #0066cc; color: white; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; min-width: 30px; display: inline-block; text-align: center;">
                                            <?php echo htmlspecialchars($pelicula['censura_codigo']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $pelicula['duracion']; ?></td>
                                <td>
                                    <?php if ($pelicula['estado'] == '1'): ?>
                                        <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 500;">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 500;">
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        </span>
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

<?php
$extra_js = '
<script>
    $(document).ready(function() {
        $(".datatable").DataTable({
            "responsive": true,
            "paging": false,
            "info": false,
            "searching": false,
            "order": [[ 0, "desc" ]],
            "language": {
                "emptyTable": "No hay películas recientes"
            }
        });
    });
</script>
';
include './includes/footer.php'; ?>