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
    
    // Consulta con conteos reales: centros → instalaciones → actividades
    $query = "
        SELECT 
            c.id, 
            c.nombre, 
            c.direccion,
            c.activo,
            COUNT(DISTINCT i.id) as total_instalaciones,
            COUNT(DISTINCT a.id) as total_actividades
        FROM centros c
        LEFT JOIN instalaciones i ON c.id = i.centro_id
        LEFT JOIN actividades a ON i.id = a.instalacion_id
    ";
    
    $params = [];
    
    // Si no es superadmin, filtrar por centros asignados
    if ($admin_info['role'] !== 'superadmin') {
        $query .= "
            INNER JOIN admin_asignaciones aa ON c.id = aa.centro_id
            WHERE aa.admin_id = ?
        ";
        $params = [$admin_info['id']];
    }
    
    $query .= " GROUP BY c.id, c.nombre, c.direccion, c.activo ORDER BY c.nombre ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
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
