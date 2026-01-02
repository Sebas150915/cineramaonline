<?php
require_once '../../config/config.php';

$page_title = "Gestión de Horarios";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Obtener todos los horarios
try {
    $stmt = $db->query("SELECT * FROM tbl_hora ORDER BY hora ASC");
    $horarios = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al obtener los horarios: ' . $e->getMessage());
    $horarios = [];
}
?>

<!-- Contenido Principal -->
<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Horarios Disponibles</h1>
            <p class="page-subtitle">Gestiona los horarios de proyección</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Horario
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Horario</th>
                    <th>Formato 12h</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horarios as $horario): ?>
                    <tr>
                        <td><?php echo $horario['id']; ?></td>
                        <td>
                            <strong style="font-size: 16px; color: var(--cinerama-red);">
                                <?php echo date('H:i', strtotime($horario['hora'])); ?>
                            </strong>
                        </td>
                        <td>
                            <span style="background: #0066cc; color: white; padding: 6px 12px; border-radius: 6px; font-weight: bold;">
                                <?php echo date('h:i A', strtotime($horario['hora'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $horario['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $horario['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                data-name="<?php echo date('h:i A', strtotime($horario['hora'])); ?>"
                                title="Eliminar">
                                <i class="fas fa-trash"></i>
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
             "order": [[ 1, "asc" ]] // Sort by Horario (Column 1)
        });
    });
</script>
';
include '../../includes/footer.php'; ?>