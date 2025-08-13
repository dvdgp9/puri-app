<?php
/**
 * Estadísticas específicas de un centro
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Obtener centro_id del parámetro GET
    $centro_id = intval($_GET['centro_id'] ?? 0);
    
    if ($centro_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de centro inválido']);
        exit;
    }
    
    // Verificar autorización del centro
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado para este centro']);
            exit;
        }
    }
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }
    
    $stats = [];
    
    // Total de instalaciones del centro
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instalaciones WHERE centro_id = ?");
    $stmt->execute([$centro_id]);
    $stats['total_instalaciones'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades activas del centro
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ? 
        AND (a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()))
    ");
    $stmt->execute([$centro_id]);
    $stats['total_actividades_activas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades programadas del centro
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ? 
        AND a.fecha_inicio > CURDATE()
    ");
    $stmt->execute([$centro_id]);
    $stats['total_actividades_programadas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de participantes del centro
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM inscritos ins
        INNER JOIN actividades a ON ins.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ?
    ");
    $stmt->execute([$centro_id]);
    $stats['total_participantes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error en API stats/center: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
