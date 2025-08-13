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

    // Filtrar por centros asignados si no es superadmin
    if ($admin_info['role'] === 'superadmin') {
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE activo = 1 ORDER BY nombre");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, nombre 
             FROM centros 
             WHERE activo = 1 
               AND id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?) 
             ORDER BY nombre"
        );
        $stmt->execute([$admin_info['id']]);
    }
    
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'centros' => $centros,
        'debug' => [
            'admin_id' => $admin_info['id'],
            'admin_role' => $admin_info['role'],
            'total_centros' => count($centros)
        ]
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
