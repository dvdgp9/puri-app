<?php
/**
 * Obtener datos de una actividad específica
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    $admin_info = getAdminInfo();
    
    // Obtener ID de la actividad desde los parámetros
    $actividad_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de actividad inválido'
        ]);
        exit;
    }
    
    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id FROM actividades WHERE id = ?");
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
    
    // Obtener datos de la actividad
    $stmt = $pdo->prepare(
        "SELECT id, nombre, instalacion_id, fecha_inicio, fecha_fin, dias_semana, hora_inicio, hora_fin
         FROM actividades 
         WHERE id = ?"
    );
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
    
    echo json_encode([
        'success' => true,
        'data' => $actividad
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en API actividades/get: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API actividades/get: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
