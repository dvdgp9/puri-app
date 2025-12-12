<?php
/**
 * Listar centros asignados al administrador
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../../../config/config.php';
require_once '../../auth_middleware.php';

try {
    $admin_info = getAdminInfo();
    
    // Parámetros de búsqueda y filtrado
    $search = $_GET['search'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 20), 100); // Máximo 100 elementos
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Base query
    $where_conditions = [];
    $params = [];
    
    // Si no es superadmin, filtrar por centros asignados
    if ($admin_info['role'] !== 'superadmin') {
        $where_conditions[] = "c.id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
        $params[] = $admin_info['id'];
    }
    
    // Filtro de búsqueda
    if (!empty($search)) {
        $where_conditions[] = "(c.nombre LIKE ? OR c.direccion LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Contar total de registros
    $count_query = "SELECT COUNT(*) as total FROM centros c $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener registros con paginación - consulta simplificada
    $query = "
        SELECT 
            c.id,
            c.nombre,
            c.direccion
        FROM centros c
        $where_clause
        ORDER BY c.nombre ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $centros,
        'centros' => $centros, // Alias para compatibilidad
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en API centros/list: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
