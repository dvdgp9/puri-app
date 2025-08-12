<?php
require_once '../../../config/config.php';

// Verificar autenticación de admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $admin_id = $_SESSION['admin_id'];
    $is_superadmin = $_SESSION['is_superadmin'] ?? false;
    
    if ($is_superadmin) {
        // Superadmin ve todos los centros
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE activo = 1 ORDER BY nombre");
        $stmt->execute();
    } else {
        // Admin normal solo ve sus centros
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE admin_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$admin_id]);
    }
    
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'centros' => $centros
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching centros for selector: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
