<?php
/**
 * Actualizar una actividad
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
    
    // Validar datos requeridos
    $actividad_id = isset($input['id']) ? intval($input['id']) : 0;
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    $fecha_inicio = isset($input['fecha_inicio']) ? $input['fecha_inicio'] : '';
    $dias_semana = isset($input['dias_semana']) ? $input['dias_semana'] : '';
    $hora_inicio = isset($input['hora_inicio']) ? $input['hora_inicio'] : '';
    $hora_fin = isset($input['hora_fin']) ? $input['hora_fin'] : '';
    
    if ($actividad_id <= 0 || empty($nombre) || empty($fecha_inicio) || empty($dias_semana) || empty($hora_inicio) || empty($hora_fin)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos incompletos'
        ]);
        exit;
    }
    
    // Validar formato de fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Formato de fecha de inicio inválido'
        ]);
        exit;
    }
    
    if (!empty($input['fecha_fin']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['fecha_fin'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Formato de fecha de fin inválido'
        ]);
        exit;
    }
    
    // Validar formato de horas
    if (!preg_match('/^\d{2}:\d{2}$/', $hora_inicio) || !preg_match('/^\d{2}:\d{2}$/', $hora_fin)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Formato de hora inválido'
        ]);
        exit;
    }
    
    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id, instalacion_id FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
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
    
    // Verificar que no haya conflictos de horario en la misma instalación
    $fecha_fin = !empty($input['fecha_fin']) ? $input['fecha_fin'] : null;
    
    $stmt = $pdo->prepare(
        "SELECT id FROM actividades 
         WHERE instalacion_id = ? AND id != ? 
           AND ( 
             (fecha_inicio <= ? AND (fecha_fin IS NULL OR fecha_fin >= ?)) OR
             (fecha_inicio <= ? AND (fecha_fin IS NULL OR fecha_fin >= ?)) OR
             (? <= fecha_inicio AND ? >= fecha_inicio)
           )
           AND ( 
             (hora_inicio < ? AND hora_fin > ?) OR
             (hora_inicio < ? AND hora_fin > ?) OR
             (? <= hora_inicio AND ? >= hora_inicio)
           )
           AND FIND_IN_SET(?, dias_semana) > 0"
    );
    
    $stmt->execute([
        $actividad['instalacion_id'], $actividad_id,
        $fecha_inicio, $fecha_inicio,
        $fecha_fin, $fecha_fin,
        $fecha_inicio, $fecha_fin,
        $hora_inicio, $hora_inicio,
        $hora_fin, $hora_fin,
        $hora_inicio, $hora_fin,
        explode(',', $dias_semana)[0] // Check first day for conflicts
    ]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Ya existe una actividad con horario conflictivo en esta instalación'
        ]);
        exit;
    }
    
    // Actualizar la actividad
    $stmt = $pdo->prepare(
        "UPDATE actividades 
         SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, dias_semana = ?, hora_inicio = ?, hora_fin = ?
         WHERE id = ?"
    );
    
    $result = $stmt->execute([
        $nombre, $fecha_inicio, $fecha_fin, $dias_semana, $hora_inicio, $hora_fin, $actividad_id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Actividad actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la actividad');
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en API actividades/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API actividades/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
