<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();
    
    $admin_id = $admin_info['id'];
    $is_superadmin = $admin_info['is_superadmin'];
    
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
    
} catch (Exception $e) {
    error_log("Error in list_for_selector.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>
