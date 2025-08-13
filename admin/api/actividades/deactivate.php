<?php
/**
 * Desactivar una actividad
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Obtener datos del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos inválidos'
        ]);
        exit;
    }
    
    // Validar ID de actividad
    $actividad_id = isset($input['id']) ? intval($input['id']) : 0;
    
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de actividad inválido'
        ]);
        exit;
    }
    
    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id, instalacion_id FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Actividad no encontrada'
        ]);
        exit;
    }
    
    // Autorización: si no es superadmin, validar que la actividad pertenezca a una instalación de un centro asignado
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM actividades a
             INNER JOIN instalaciones i ON a.instalacion_id = i.id
             INNER JOIN admin_asignaciones aa ON aa.centro_id = i.centro_id
             WHERE a.id = ? AND aa.admin_id = ?"
        );
        $stmt->execute([$actividad_id, $admin_info['id']]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado para esta actividad'
            ]);
            exit;
        }
    }
    
    // Verificar que la actividad no tenga asistencias registradas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM asistencias WHERE actividad_id = ?");
    $stmt->execute([$actividad_id]);
    $asistencias = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($asistencias > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No se puede desactivar una actividad con asistencias registradas'
        ]);
        exit;
    }
    
    // Desactivar la actividad (en realidad la eliminamos por simplicidad)
    $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
    $result = $stmt->execute([$actividad_id]);
    
    if ($result) {
        // También eliminamos los participantes inscritos
        $stmt = $pdo->prepare("DELETE FROM inscritos WHERE actividad_id = ?");
        $stmt->execute([$actividad_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Actividad desactivada correctamente'
        ]);
    } else {
        throw new Exception('Error al desactivar la actividad');
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en API actividades/deactivate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API actividades/deactivate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
