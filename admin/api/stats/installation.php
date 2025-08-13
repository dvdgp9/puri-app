<?php
/**
 * Estadísticas para una instalación específica
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Obtener ID de la instalación desde los parámetros
    $instalacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($instalacion_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de instalación inválido'
        ]);
        exit;
    }
    
    // Verificar que la instalación existe
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE id = ?");
    $stmt->execute([$instalacion_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Instalación no encontrada'
        ]);
        exit;
    }
    
    // Autorización: si no es superadmin, validar que la instalación pertenezca a un centro asignado
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM instalaciones i
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE i.id = ? AND aa.admin_id = ?"
        );
        $stmt->execute([$instalacion_id, $admin_info['id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado para esta instalación'
            ]);
            exit;
        }
    }
    
    // Estadísticas de la instalación
    $stats = [];
    
    // Total de actividades
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades 
         WHERE instalacion_id = ?"
    );
    $stmt->execute([$instalacion_id]);
    $stats['total_actividades'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades activas (entre fecha_inicio y fecha_fin o sin fecha_fin)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades 
         WHERE instalacion_id = ? 
           AND (fecha_inicio <= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()))"
    );
    $stmt->execute([$instalacion_id]);
    $stats['actividades_activas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades programadas (fecha_inicio > hoy)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades 
         WHERE instalacion_id = ? 
           AND fecha_inicio > CURDATE()"
    );
    $stmt->execute([$instalacion_id]);
    $stats['actividades_programadas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de participantes en todas las actividades de esta instalación
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT i.id) as total
         FROM inscritos i
         INNER JOIN actividades a ON i.actividad_id = a.id
         WHERE a.instalacion_id = ?"
    );
    $stmt->execute([$instalacion_id]);
    $stats['total_participantes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en API stats/installation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API stats/installation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
