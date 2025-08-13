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
    
    // Obtener centro_id del parámetro GET
    $centro_id = intval($_GET['centro_id'] ?? 0);
    
    if ($centro_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de centro inválido']);
        exit;
    }
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id FROM centros WHERE id = ? AND activo = 1");
    $stmt->execute([$centro_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }
    
    // Autorización: si no es superadmin, validar asignación del centro
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para este centro']);
            exit;
        }
    }
    
    // Parámetros de búsqueda y ordenación
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'nombre';
    
    // Construir WHERE clause para búsqueda
    $where_conditions = ["i.centro_id = ?"];
    $params = [$centro_id];
    
    if (!empty($search)) {
        $where_conditions[] = "i.nombre LIKE ?";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Determinar ORDER BY
    $order_by = "i.nombre ASC";
    if ($sort === '-nombre') {
        $order_by = "i.nombre DESC";
    }
    
    // Obtener instalaciones del centro con estadísticas de actividades
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.nombre,
            COUNT(CASE WHEN a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()) THEN 1 END) as total_actividades_activas,
            COUNT(CASE WHEN a.fecha_inicio > CURDATE() THEN 1 END) as total_actividades_programadas,
            COUNT(CASE WHEN a.fecha_fin < CURDATE() THEN 1 END) as total_actividades_finalizadas
        FROM instalaciones i
        LEFT JOIN actividades a ON i.id = a.instalacion_id
        WHERE $where_clause
        GROUP BY i.id, i.nombre
        ORDER BY $order_by
    ");
    $stmt->execute($params);
    
    $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'instalaciones' => $instalaciones
    ]);
    
} catch (Exception $e) {
    error_log("Error in instalaciones/list_by_center.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
