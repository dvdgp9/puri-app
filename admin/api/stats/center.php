<?php
/**
 * Estadísticas específicas de un centro
 */

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
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$centro) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
        exit;
    }
    
    $stats = [];
    
    // Total de asistencias en el centro
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM asistencias asist
        INNER JOIN actividades a ON asist.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE i.centro_id = ? AND asist.asistio = 1
    ");
    $stmt->execute([$centro_id]);
    $stats['total_asistencias'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Asistencias de esta semana
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM asistencias asist
        INNER JOIN actividades a ON asist.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE i.centro_id = ? 
          AND asist.asistio = 1
          AND asist.fecha >= DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY)
          AND asist.fecha <= DATE_ADD(DATE_SUB(CURDATE(), INTERVAL DAYOFWEEK(CURDATE())-1 DAY), INTERVAL 6 DAY)
    ");
    $stmt->execute([$centro_id]);
    $stats['asistencias_semana'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Ocupación media (porcentaje de asistencia)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_registros,
            SUM(CASE WHEN asist.asistio = 1 THEN 1 ELSE 0 END) as total_asistencias
        FROM asistencias asist
        INNER JOIN actividades a ON asist.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE i.centro_id = ?
    ");
    $stmt->execute([$centro_id]);
    $ocupacion_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['ocupacion_media'] = 0;
    if ($ocupacion_data['total_registros'] > 0) {
        $stats['ocupacion_media'] = round(
            ($ocupacion_data['total_asistencias'] / $ocupacion_data['total_registros']) * 100, 
            0
        );
    }
    
    // Total de actividades del centro
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM actividades a
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE i.centro_id = ?
    ");
    $stmt->execute([$centro_id]);
    $stats['total_actividades'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Número de instalaciones
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instalaciones WHERE centro_id = ?");
    $stmt->execute([$centro_id]);
    $stats['total_instalaciones'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Miembros activos (participantes únicos con asistencias recientes)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ins.id) as total
        FROM inscritos ins
        INNER JOIN actividades a ON ins.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        INNER JOIN asistencias asist ON asist.actividad_id = a.id AND asist.usuario_id = ins.id
        WHERE i.centro_id = ? 
          AND asist.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND asist.asistio = 1
    ");
    $stmt->execute([$centro_id]);
    $stats['miembros_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error in center stats API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
