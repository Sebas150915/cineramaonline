<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// Permissions
if ($_SESSION['rol'] !== 'superadmin' && $_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'supervisor') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Fetch ingredients (insumos) and products that can be part of a recipe
$raw_insumos = $db->query("SELECT id, nombre, unidad_medida FROM tbl_productos WHERE estado = '1' ORDER BY nombre ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Check
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

        // Validation
        if (empty($nombre) || empty($tipo)) {
            showAlert('error', 'Error', 'Nombre y Tipo son obligatorios.');
        } else {
            try {
                $db->beginTransaction();

                // Insert Product
                $stmt = $db->prepare("INSERT INTO tbl_productos (nombre, tipo, codigo_barras, precio_venta, precio_base, igv_tipo, es_vendible, stock, unidad_medida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $tipo, $codigo, $precio_venta, $precio_base, $igv_tipo, $es_vendible, $stock, $unidad]);
                $producto_id = $db->lastInsertId();

                // Save Recipe if Combo
                if ($tipo === 'combo' && !empty($_POST['receta_id'])) {
                    $insumosIds = $_POST['receta_id']; // Array
                    $cantidades = $_POST['receta_qty']; // Array

                    $stmtReceta = $db->prepare("INSERT INTO tbl_recetas (id_producto_padre, id_producto_hijo, cantidad) VALUES (?, ?, ?)");

                    for ($i = 0; $i < count($insumosIds); $i++) {
                        if (!empty($insumosIds[$i]) && !empty($cantidades[$i])) {
                            $stmtReceta->execute([$producto_id, $insumosIds[$i], $cantidades[$i]]);
                        }
                    }
                }

                $db->commit();
                showAlert('success', 'Éxito', 'Producto creado correctamente.');
                redirect('index.php');
            } catch (PDOException $e) {
                $db->rollBack();
                error_log($e->getMessage());
                showAlert('error', 'Error', 'Error al guardar producto.');
            }
        }
    }
}

$page_title = "Nuevo Producto";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Nuevo Producto</h1>
        </div>
        <div><a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a></div>
    </div>

    <div class="card">
        <form method="POST" id="productForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="grid-form">
                <!-- Basic Info -->
                <div class="form-group">
                    <label class="required">Nombre Producto</label>
                    <input type="text" name="nombre" class="form-control" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Código Barras</label>
                    <input type="text" name="codigo_barras" class="form-control">
                </div>

                <div class="form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" id="tipo" class="form-control" required onchange="toggleSections()">
                        <option value="producto">Producto Simple (Unitario)</option>
                        <option value="insumo">Insumo (Materia Prima)</option>
                        <option value="combo">Combo / Preparado (Receta)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Venta</label>
                    <div class="checkbox-wrapper" style="margin-top: 10px;">
                        <input type="checkbox" name="es_vendible" id="es_vendible" value="1" checked>
                        <label for="es_vendible">¿Se puede vender en caja?</label>
                    </div>
                </div>
            </div>

            <!-- Price & Tax Section (Hidden for Insumos usually, but editable) -->
            <div class="section-divider">Precios e Impuestos</div>
            <div class="grid-form">
                <div class="form-group">
                    <label>Precio Venta Total (Inc. IGV)</label>
                    <input type="number" name="precio_venta" id="precio_venta" class="form-control" step="0.01" oninput="calculateBase()">
                </div>

                <div class="form-group">
                    <label>Tipo IGV</label>
                    <select name="igv_tipo" id="igv_tipo" class="form-control" onchange="calculateBase()">
                        <option value="gravado">Gravado (18%)</option>
                        <option value="exonerado">Exonerado (0%)</option>
                        <option value="inafecto">Inafecto (0%)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Precio Base (Sin IGV)</label>
                    <input type="number" name="precio_base" id="precio_base" class="form-control" step="0.01" readonly>
                </div>
            </div>

            <!-- Inventory / Stock (Only for Simple/Insumo) -->
            <div id="stockSection">
                <div class="section-divider">Inventario Inicial</div>
                <div class="grid-form">
                    <div class="form-group">
                        <label>Stock Actual</label>
                        <input type="number" name="stock" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Unidad de Medida</label>
                        <select name="unidad_medida" class="form-control">
                            <option value="NIU">Unidad (NIU)</option>
                            <option value="KG">Kilogramos (KG)</option>
                            <option value="LTR">Litros (LTR)</option>
                            <option value="CJA">Caja (CJA)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Recipe Section (Only for Combo) -->
            <div id="recipeSection" style="display: none;">
                <div class="section-divider">Receta / Composición</div>
                <p class="text-muted mb-20">Agrega los insumos que se descontarán al vender este combo.</p>

                <table class="table table-bordered" id="recipeTable">
                    <thead>
                        <tr>
                            <th>Insumo / Producto</th>
                            <th width="150">Cantidad</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows added via JS -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary btn-sm mt-10" onclick="addIngredientRow()">
                    <i class="fas fa-plus"></i> Agregar Insumo
                </button>
            </div>

            <div class="mt-20">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Producto</button>
            </div>
        </form>
    </div>
</main>

<script>
    // Data for dropdowns
    const allInsumos = <?php echo json_encode($raw_insumos); ?>;

    function toggleSections() {
        const tipo = document.getElementById('tipo').value;
        const stockSec = document.getElementById('stockSection');
        const recipeSec = document.getElementById('recipeSection');
        const sellableCheck = document.getElementById('es_vendible');

        if (tipo === 'combo') {
            stockSec.style.display = 'none';
            recipeSec.style.display = 'block';
            sellableCheck.checked = true; // Combos usually sellable
        } else if (tipo === 'insumo') {
            stockSec.style.display = 'block';
            recipeSec.style.display = 'none';
            sellableCheck.checked = false; // Insumos usually not sellable
        } else {
            stockSec.style.display = 'block';
            recipeSec.style.display = 'none';
            sellableCheck.checked = true;
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
        <td>
            <select name="receta_id[]" class="form-control" required>${options}</select>
        </td>
        <td>
            <input type="number" name="receta_qty[]" class="form-control" step="0.01" required placeholder="Cant.">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button>
        </td>
    `;
        tbody.appendChild(row);
    }

    // Init
    document.addEventListener('DOMContentLoaded', toggleSections);
</script>

<?php include '../../includes/footer.php'; ?>