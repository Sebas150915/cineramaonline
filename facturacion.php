<?php
require_once 'includes/front_config.php';

$mensaje = "";
$ventas = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $criterio = isset($_POST['criterio']) ? trim($_POST['criterio']) : '';

    if (!empty($criterio)) {
        try {
            // Search by Ticket Code (B001-...) or Client Doc (DNI/RUC)
            $stmt = $db->prepare("
                SELECT v.*, f.fecha, p.nombre as pelicula 
                FROM tbl_ventas v
                JOIN tbl_funciones f ON v.id_funcion = f.id
                JOIN tbl_pelicula p ON f.id_pelicula = p.id
                WHERE (v.codigo LIKE ? OR v.cliente_doc = ?) 
                AND v.estado = 'PAGADO'
                ORDER BY v.created_at DESC
                LIMIT 20
            ");
            $stmt->execute(["%$criterio%", $criterio]);
            $ventas = $stmt->fetchAll();

            if (empty($ventas)) {
                $mensaje = "No se encontraron documentos con ese criterio.";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al buscar: " . $e->getMessage();
        }
    } else {
        $mensaje = "Por favor ingrese un código o documento.";
    }
}

$page_title = "Consulta de Comprobantes";
include 'includes/header_front.php';
?>

<div class="container" style="max-width: 800px; min-height: 500px;">
    <h2 class="section-title">Consulta de Comprobantes</h2>
    <p style="color: #ccc; margin-bottom: 20px;">Busca y descarga tus boletas y facturas electrónicas.</p>

    <div class="card" style="padding: 30px;">
        <form method="POST" action="" style="display: flex; gap: 10px;">
            <input type="text" name="criterio" placeholder="Ingresa tu DNI, RUC o Código de Ticket (Ej: B001-...)" required
                style="flex: 1; padding: 12px; border-radius: 5px; border: none; font-size: 16px;">
            <button type="submit" class="btn">Buscar</button>
        </form>
    </div>

    <?php if ($mensaje): ?>
        <div style="margin-top: 20px; padding: 15px; background: #333; color: #fff; border-radius: 5px;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($ventas)): ?>
        <div style="margin-top: 30px;">
            <h3 style="border-bottom: 1px solid #444; padding-bottom: 10px; margin-bottom: 15px;">Resultados</h3>

            <table style="width: 100%; border-collapse: collapse; color: #fff;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #555;">
                        <th style="padding: 10px;">Fecha</th>
                        <th style="padding: 10px;">Documento</th>
                        <th style="padding: 10px;">Película</th>
                        <th style="padding: 10px;">Total</th>
                        <th style="padding: 10px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $v): ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($v['created_at'])); ?></td>
                            <td style="padding: 12px;">
                                <strong style="color: #e50914;"><?php echo htmlspecialchars($v['codigo']); ?></strong><br>
                                <small style="color: #888;"><?php echo $v['tipo_comprobante']; ?></small>
                            </td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($v['pelicula']); ?></td>
                            <td style="padding: 12px;">S/ <?php echo number_format($v['total'], 2); ?></td>
                            <td style="padding: 12px;">
                                <a href="ticket.php?codigo=<?php echo $v['codigo']; ?>" target="_blank"
                                    style="color: #fff; text-decoration: underline;">Ver Ticket</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer_front.php'; ?>