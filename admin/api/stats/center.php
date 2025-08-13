<?php
/**
 * Estadísticas para un centro específico
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Obtener ID del centro desde los parámetros
    $centro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($centro_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de centro inválido'
        ]);
        exit;
    }
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT id FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Centro no encontrado'
        ]);
        exit;
    }
    
    // Verificar permisos del administrador
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado para este centro'
            ]);
            exit;
        }
    }
    
    // Estadísticas del centro
    $stats = [];
    
    // Total de instalaciones
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instalaciones WHERE centro_id = ?");
    $stmt->execute([$centro_id]);
    $stats['total_instalaciones'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades activas (entre fecha_inicio y fecha_fin o sin fecha_fin)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades a 
         INNER JOIN instalaciones i ON a.instalacion_id = i.id 
         WHERE i.centro_id = ? 
           AND (a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()))"
    );
    $stmt->execute([$centro_id]);
    $stats['total_actividades_activas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades programadas (fecha_inicio > hoy)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades a 
         INNER JOIN instalaciones i ON a.instalacion_id = i.id 
         WHERE i.centro_id = ? 
           AND a.fecha_inicio > CURDATE()"
    );
    $stmt->execute([$centro_id]);
    $stats['total_actividades_programadas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calcular porcentaje de asistencia (solo actividades activas)
    $stmt = $pdo->prepare(
        "SELECT 
            COUNT(*) as total_registros,
            SUM(CASE WHEN asist.asistio = 1 THEN 1 ELSE 0 END) as total_asistencias
         FROM asistencias asist
         INNER JOIN actividades a ON asist.actividad_id = a.id
         INNER JOIN instalaciones i ON a.instalacion_id = i.id
         WHERE i.centro_id = ?
           AND (a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()))"
    );
    $stmt->execute([$centro_id]);
    $asistencia_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['porcentaje_asistencia'] = 0;
    $stats['total_asistencias'] = (int)$asistencia_data['total_asistencias'];
    
    if ($asistencia_data['total_registros'] > 0) {
        $stats['porcentaje_asistencia'] = round(
            ($asistencia_data['total_asistencias'] / $asistencia_data['total_registros']) * 100, 
            1
        );
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en API stats/center: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API stats/center: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
