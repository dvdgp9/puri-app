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
    
    // Obtener instalaciones del centro con conteos de actividades
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.nombre,
            COUNT(CASE WHEN a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()) THEN 1 END) as actividades_activas,
            COUNT(CASE WHEN a.fecha_inicio > CURDATE() THEN 1 END) as actividades_programadas,
            COUNT(CASE WHEN a.fecha_fin < CURDATE() THEN 1 END) as actividades_finalizadas
        FROM instalaciones i
        LEFT JOIN actividades a ON i.id = a.instalacion_id
        WHERE i.centro_id = ?
        GROUP BY i.id, i.nombre
        ORDER BY i.nombre
    ");
    $stmt->execute([$centro_id]);
    
    $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir conteos a enteros
    foreach ($instalaciones as &$instalacion) {
        $instalacion['actividades_activas'] = (int)$instalacion['actividades_activas'];
        $instalacion['actividades_programadas'] = (int)$instalacion['actividades_programadas'];
        $instalacion['actividades_finalizadas'] = (int)$instalacion['actividades_finalizadas'];
    }
    
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
