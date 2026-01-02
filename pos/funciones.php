<?php
define('IS_POS', true);
require_once '../panel/config/config.php';
require_once 'includes/auth_check.php';

$id_pelicula = $_GET['id'] ?? null;
if (!$id_pelicula) {
    header("Location: dashboard.php");
    exit;
}

// Get movie details
$stmt = $db->prepare("SELECT * FROM tbl_pelicula WHERE id = ?");
$stmt->execute([$id_pelicula]);
$pelicula = $stmt->fetch();

// Get functions (Next 7 days?) or all future functions
// Group by Date -> Cinema -> Time
// Ordering by Date ASC, Time ASC
try {
    $sql = "
        SELECT f.*, l.nombre as cine, s.nombre as sala, h.hora as hora_inicio
        FROM tbl_funciones f
        JOIN tbl_locales l ON f.id_local = l.id
        JOIN tbl_sala s ON f.id_sala = s.id
        JOIN tbl_hora h ON f.id_hora = h.id
        WHERE f.id_pelicula = ? 
        AND f.estado = '1' 
        AND f.fecha >= CURRENT_DATE
        ORDER BY f.fecha ASC, h.hora ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_pelicula]);
    $funciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $funciones = [];
}

include 'includes/header.php';
?>

<div class="back-btn" style="margin-bottom: 20px;">
    <a href="dashboard.php" style="text-decoration: none; color: #333;"><i class="fas fa-arrow-left"></i> Volver a Películas</a>
</div>

<h2><?php echo htmlspecialchars($pelicula['nombre']); ?> - Funciones Disponibles</h2>

<div class="functions-list">
    <?php if (empty($funciones)): ?>
        <div class="alert alert-info">No hay funciones programadas para esta película.</div>
    <?php else: ?>
        <?php
        $current_date = '';
        foreach ($funciones as $func):
            if ($current_date != $func['fecha']):
                $current_date = $func['fecha'];
        ?>
                <h3 style="margin-top: 20px; border-bottom: 2px solid #ddd; padding-bottom: 5px;">
                    <?php echo date('d/m/Y', strtotime($current_date)); ?>
                </h3>
            <?php endif; ?>

            <a href="sala.php?id_funcion=<?php echo $func['id']; ?>" class="func-item" style="display: block; background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; text-decoration: none; color: #333; border-left: 5px solid #c01820; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-weight: bold; font-size: 1.2rem;"><?php echo date('H:i', strtotime($func['hora_inicio'])); ?></span>
                        <span style="color: #666; margin-left: 10px;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($func['cine']); ?> - <?php echo htmlspecialchars($func['sala']); ?>
                        </span>
                    </div>
                    <div class="btn-select" style="background: #c01820; color: white; padding: 5px 15px; border-radius: 4px;">
                        Seleccionar
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .func-item:hover {
        background: #f9f9f9;
        transform: translateX(5px);
        transition: 0.2s;
    }
</style>

<?php include 'includes/footer.php'; ?>