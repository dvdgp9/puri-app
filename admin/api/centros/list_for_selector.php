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
    
    // Por ahora, todos los admins ven todos los centros activos
    // TODO: Implementar admin_asignaciones cuando esté configurada
    $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE activo = 1 ORDER BY nombre");
    $stmt->execute();
    
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
