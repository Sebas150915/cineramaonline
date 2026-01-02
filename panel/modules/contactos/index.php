<?php
require_once '../../config/config.php';

$page_title = "Mensajes de Contacto";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener mensajes
try {
    $stmt = $db->query("SELECT * FROM tbl_contactos ORDER BY fecha DESC");
    $mensajes = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    $mensajes = [];
}
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Mensajes Recibidos</h1>
            <p class="page-subtitle">Bandeja de entrada de contacto</p>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cine</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mensajes as $msg): ?>
                    <tr>
                        <td><?php echo $msg['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($msg['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($msg['cine']); ?></td>
                        <td><?php echo htmlspecialchars($msg['nombre'] . ' ' . $msg['apellidos']); ?></td>
                        <td><?php echo htmlspecialchars($msg['correo']); ?></td>
                        <td><?php echo htmlspecialchars($msg['asunto']); ?></td>
                        <td>
                            <?php if ($msg['estado'] == '1'): ?>
                                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">Leído</span>
                            <?php else: ?>
                                <span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px;">No Leído</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $msg['id']; ?>" class="btn btn-info btn-sm" title="Ver Mensaje">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>


<?php
$extra_js = '
<script>
    $(document).ready(function() {
        $(".datatable").DataTable({
            "order": [[ 1, "desc" ]] // Sort by date desc
        });
    });
</script>
';
include '../../includes/footer.php'; ?>