<?php
/**
 * Listar centros - Versión nueva y limpia
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación
    $admin_info = getAdminInfo();
    
    // Consulta ultra-simple - solo los campos que existen
    $query = "SELECT id, nombre, direccion FROM centros ORDER BY nombre ASC";
    
    // Si no es superadmin, filtrar por centros asignados
    if ($admin_info['role'] !== 'superadmin') {
        $query = "
            SELECT c.id, c.nombre, c.direccion 
            FROM centros c
            INNER JOIN admin_asignaciones aa ON c.id = aa.centro_id
            WHERE aa.admin_id = ?
            ORDER BY c.nombre ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$admin_info['id']]);
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    }
    
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $centros,
        'total' => count($centros)
    ]);
    
} catch (Exception $e) {
    error_log("Error en centros/list_new: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
