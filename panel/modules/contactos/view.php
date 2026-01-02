<?php
require_once '../../config/config.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Mark as read
    $update = $db->prepare("UPDATE tbl_contactos SET estado = '1' WHERE id = ?");
    $update->execute([$id]);

    // Get message details
    $stmt = $db->prepare("SELECT * FROM tbl_contactos WHERE id = ?");
    $stmt->execute([$id]);
    $msg = $stmt->fetch();

    if (!$msg) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$page_title = "Ver Mensaje";
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="admin-content">
    <div class="content-header">
        <div>
            <h1 class="page-title">Detalle del Mensaje</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #333;">
        <div style="margin-bottom: 20px;">
            <strong style="display: block; font-size: 14px; color: #666;">DE:</strong>
            <span style="font-size: 18px; font-weight: bold;"><?php echo htmlspecialchars($msg['nombre'] . ' ' . $msg['apellidos']); ?></span>
            <br>
            <span style="color: #666;"><?php echo htmlspecialchars($msg['correo']); ?></span>
        </div>

        <div style="margin-bottom: 20px;">
            <strong style="display: block; font-size: 14px; color: #666;">PARA CINE:</strong>
            <span style="font-size: 16px;"><?php echo htmlspecialchars($msg['cine']); ?></span>
        </div>

        <div style="margin-bottom: 20px;">
            <strong style="display: block; font-size: 14px; color: #666;">ASUNTO:</strong>
            <span style="font-size: 16px;"><?php echo htmlspecialchars($msg['asunto']); ?></span>
        </div>

        <div style="margin-bottom: 20px;">
            <strong style="display: block; font-size: 14px; color: #666;">FECHA:</strong>
            <span style="font-size: 14px;"><?php echo date('d/m/Y H:i', strtotime($msg['fecha'])); ?></span>
        </div>

        <hr>

        <div style="margin-top: 20px;">
            <strong style="display: block; font-size: 14px; color: #666; margin-bottom: 10px;">MENSAJE:</strong>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 4px; border: 1px solid #eee; white-space: pre-wrap;"><?php echo htmlspecialchars($msg['mensaje']); ?></div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>