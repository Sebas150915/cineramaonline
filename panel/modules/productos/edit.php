<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Product
$stmt = $db->prepare("SELECT * FROM tbl_productos WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    header("Location: index.php");
    exit;
}

// Fetch Recipe if combo
$currentRecipe = [];
if ($product['tipo'] === 'combo') {
    $stmtR = $db->prepare("SELECT r.*, p.nombre, p.unidad_medida FROM tbl_recetas r JOIN tbl_productos p ON r.id_producto_hijo = p.id WHERE r.id_producto_padre = ?");
    $stmtR->execute([$id]);
    $currentRecipe = $stmtR->fetchAll();
}

$raw_insumos = $db->query("SELECT id, nombre, unidad_medida FROM tbl_productos WHERE estado = '1' AND id != $id ORDER BY nombre ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        showAlert('error', 'Error', 'Error seguridad token.');
    } else {
        $nombre = sanitize($_POST['nombre']);
        $tipo = sanitize($_POST['tipo']);
        $codigo = sanitize($_POST['codigo_barras']);
        $es_vendible = isset($_POST['es_vendible']) ? 1 : 0;
        $precio_venta = !empty($_POST['precio_venta']) ? $_POST['precio_venta'] : 0.00;
        $precio_base = !empty($_POST['precio_base']) ? $_POST['precio_base'] : 0.00;
        $igv_tipo = sanitize($_POST['igv_tipo']);
        $stock = !empty($_POST['stock']) ? $_POST['stock'] : 0.00;
        $unidad = sanitize($_POST['unidad_medida']);
        $estado = isset($_POST['estado']) ? '1' : '0';

        if (empty($nombre)) {
            showAlert('error', 'Error', 'Nombre obligatorio.');
        } else {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("UPDATE tbl_productos SET nombre=?, tipo=?, codigo_barras=?, precio_venta=?, precio_base=?, igv_tipo=?, es_vendible=?, stock=?, unidad_medida=?, estado=? WHERE id=?");
                $stmt->execute([$nombre, $tipo, $codigo, $precio_venta, $precio_base, $igv_tipo, $es_vendible, $stock, $unidad, $estado, $id]);

                // Update Recipe
                // Simplest strategy: Delete all old recipe items and re-insert
                if ($tipo === 'combo') {
                    $db->prepare("DELETE FROM tbl_recetas WHERE id_producto_padre = ?")->execute([$id]);

                    if (!empty($_POST['receta_id'])) {
                        $insumosIds = $_POST['receta_id'];
                        $cantidades = $_POST['receta_qty'];
                        $stmtReceta = $db->prepare("INSERT INTO tbl_recetas (id_producto_padre, id_producto_hijo, cantidad) VALUES (?, ?, ?)");

                        for ($i = 0; $i < count($insumosIds); $i++) {
                            if (!empty($insumosIds[$i]) && !empty($cantidades[$i])) {
                                $stmtReceta->execute([$id, $insumosIds[$i], $cantidades[$i]]);
                            }
                        }
                    }
                }

                $db->commit();
                showAlert('success', 'Éxito', 'Producto actualizado.');
                // Refresh
                $stmt->execute([$id]); // Re-fetch won't work simply with same stmt obj re-exec on select, just redirect or simpler
                header("Location: edit.php?id=$id");
                exit;
            } catch (PDOException $e) {
                $db->rollBack();
                error_log($e->getMessage());
                showAlert('error', 'Error', 'Error al actualizar.' . $e->getMessage());
            }
        }
    }
}

$page_title = "Editar Producto";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Editar Producto: <?php echo htmlspecialchars($product['nombre']); ?></h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <form method="POST" id="productForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="grid-form">
                <div class="form-group">
                    <label class="required">Nombre Producto</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($product['nombre']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Código Barras</label>
                    <input type="text" name="codigo_barras" class="form-control" value="<?php echo htmlspecialchars($product['codigo_barras']); ?>">
                </div>

                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" id="tipo" class="form-control" required onchange="toggleSections()">
                        <option value="producto" <?php echo $product['tipo'] == 'producto' ? 'selected' : ''; ?>>Producto Simple</option>
                        <option value="insumo" <?php echo $product['tipo'] == 'insumo' ? 'selected' : ''; ?>>Insumo</option>
                        <option value="combo" <?php echo $product['tipo'] == 'combo' ? 'selected' : ''; ?>>Combo / Receta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Venta</label>
                    <div class="checkbox-wrapper" style="margin-top: 10px;">
                        <input type="checkbox" name="es_vendible" id="es_vendible" value="1" <?php echo $product['es_vendible'] ? 'checked' : ''; ?>>
                        <label for="es_vendible">¿Se puede vender en caja?</label>
                    </div>
                </div>
            </div>

            <div class="section-divider">Precios e Impuestos</div>
            <div class="grid-form">
                <div class="form-group">
                    <label>Precio Venta Total (Inc. IGV)</label>
                    <input type="number" name="precio_venta" id="precio_venta" class="form-control" step="0.01" value="<?php echo $product['precio_venta']; ?>" oninput="calculateBase()">
                </div>

                <div class="form-group">
                    <label>Tipo IGV</label>
                    <select name="igv_tipo" id="igv_tipo" class="form-control" onchange="calculateBase()">
                        <option value="gravado" <?php echo $product['igv_tipo'] == 'gravado' ? 'selected' : ''; ?>>Gravado (18%)</option>
                        <option value="exonerado" <?php echo $product['igv_tipo'] == 'exonerado' ? 'selected' : ''; ?>>Exonerado (0%)</option>
                        <option value="inafecto" <?php echo $product['igv_tipo'] == 'inafecto' ? 'selected' : ''; ?>>Inafecto (0%)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Precio Base (Sin IGV)</label>
                    <input type="number" name="precio_base" id="precio_base" class="form-control" step="0.01" value="<?php echo $product['precio_base']; ?>" readonly>
                </div>
            </div>

            <div id="stockSection">
                <div class="section-divider">Inventario</div>
                <div class="grid-form">
                    <div class="form-group">
                        <label>Stock Actual</label>
                        <input type="number" name="stock" class="form-control" step="0.01" value="<?php echo $product['stock']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Unidad de Medida</label>
                        <select name="unidad_medida" class="form-control">
                            <option value="NIU" <?php echo $product['unidad_medida'] == 'NIU' ? 'selected' : ''; ?>>Unidad (NIU)</option>
                            <option value="KG" <?php echo $product['unidad_medida'] == 'KG' ? 'selected' : ''; ?>>Kilogramos (KG)</option>
                            <option value="LTR" <?php echo $product['unidad_medida'] == 'LTR' ? 'selected' : ''; ?>>Litros (LTR)</option>
                            <option value="CJA" <?php echo $product['unidad_medida'] == 'CJA' ? 'selected' : ''; ?>>Caja (CJA)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="recipeSection" style="display: none;">
                <div class="section-divider">Receta / Composición</div>
                <p class="text-muted mb-20">Insumos que componen este combo.</p>

                <table class="table table-bordered" id="recipeTable">
                    <thead>
                        <tr>
                            <th>Insumo / Producto</th>
                            <th width="150">Cantidad</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentRecipe as $item): ?>
                            <tr>
                                <td>
                                    <select name="receta_id[]" class="form-control">
                                        <option value="<?php echo $item['id_producto_hijo']; ?>"><?php echo htmlspecialchars($item['nombre'] . ' (' . $item['unidad_medida'] . ')'); ?></option>
                                        <?php foreach ($raw_insumos as $ins):
                                            if ($ins['id'] == $item['id_producto_hijo']) continue;
                                        ?>
                                            <option value="<?php echo $ins['id']; ?>"><?php echo htmlspecialchars($ins['nombre'] . ' (' . $ins['unidad_medida'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="receta_qty[]" class="form-control" step="0.01" value="<?php echo $item['cantidad']; ?>" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary btn-sm mt-10" onclick="addIngredientRow()">
                    <i class="fas fa-plus"></i> Agregar Insumo
                </button>
            </div>

            <div class="form-group mt-20">
                <label>
                    <input type="checkbox" name="estado" value="1" <?php echo $product['estado'] == '1' ? 'checked' : ''; ?>> Activo
                </label>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar Producto</button>
            </div>
        </form>
    </div>
</main>

<script>
    const allInsumos = <?php echo json_encode($raw_insumos); ?>;

    function toggleSections() {
        const tipo = document.getElementById('tipo').value;
        const stockSec = document.getElementById('stockSection');
        const recipeSec = document.getElementById('recipeSection');

        if (tipo === 'combo') {
            stockSec.style.display = 'none';
            recipeSec.style.display = 'block';
        } else {
            stockSec.style.display = 'block';
            recipeSec.style.display = 'none';
        }
    }

    function calculateBase() {
        const total = parseFloat(document.getElementById('precio_venta').value) || 0;
        const tipoIgv = document.getElementById('igv_tipo').value;
        let base = total;

        if (tipoIgv === 'gravado') {
            base = total / 1.18;
        }
        document.getElementById('precio_base').value = base.toFixed(2);
    }

    function addIngredientRow() {
        const tbody = document.querySelector('#recipeTable tbody');
        const row = document.createElement('tr');

        let options = '<option value="">-- Seleccionar --</option>';
        allInsumos.forEach(ins => {
            options += `<option value="${ins.id}">${ins.nombre} (${ins.unidad_medida})</option>`;
        });

        row.innerHTML = `
        <td><select name="receta_id[]" class="form-control" required>${options}</select></td>
        <td><input type="number" name="receta_qty[]" class="form-control" step="0.01" required></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
    `;
        tbody.appendChild(row);
    }

    document.addEventListener('DOMContentLoaded', toggleSections);
</script>

<?php include '../../includes/footer.php'; ?>