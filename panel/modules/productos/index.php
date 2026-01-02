<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Check permissions
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$page_title = "Gestión de Productos";
include '../../includes/header.php';
include '../../includes/sidebar.php';

// Fetch products
try {
    $stmt = $db->query("SELECT * FROM tbl_productos ORDER BY nombre ASC");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    showAlert('error', 'Error', 'Error al cargar productos: ' . $e->getMessage());
    $productos = [];
}
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Productos</h1>
            <p class="page-subtitle">Dulcería y Confitería</p>
        </div>
        <div>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Producto
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="datatable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Precio Venta</th>
                    <th>Stock / Receta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $prod): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong><br>
                            <?php if ($prod['codigo_barras']): ?>
                                <small class="text-muted"><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($prod['codigo_barras']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge = 'secondary';
                            if ($prod['tipo'] == 'producto') $badge = 'primary';
                            if ($prod['tipo'] == 'combo') $badge = 'success';
                            if ($prod['tipo'] == 'insumo') $badge = 'warning';
                            ?>
                            <span class="badge badge-<?php echo $badge; ?>">
                                <?php echo ucfirst($prod['tipo']); ?>
                            </span>
                            <?php if (!$prod['es_vendible']): ?>
                                <span class="badge badge-danger" title="No se muestra en caja"><i class="fas fa-eye-slash"></i> No Venta</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prod['es_vendible']): ?>
                                S/ <?php echo number_format($prod['precio_venta'], 2); ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prod['tipo'] == 'combo'): ?>
                                <span class="text-info"><i class="fas fa-list"></i> Receta</span>
                            <?php else: ?>
                                <strong><?php echo floatval($prod['stock']); ?></strong> <?php echo htmlspecialchars($prod['unidad_medida']); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($prod['estado'] == '1'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $prod['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $prod['id']; ?>"
                                class="btn btn-danger btn-sm btn-delete"
                                title="Eliminar"
                                onclick="return confirm('¿Eliminar este producto?');">
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
        $(".datatable").DataTable();
    });
</script>
';
include '../../includes/footer.php'; ?>