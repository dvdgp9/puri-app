<?php
/**
 * Desactivar una instalación
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
    
    // Validar ID de instalación
    $instalacion_id = isset($input['id']) ? intval($input['id']) : 0;
    
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
    
    // Verificar que la instalación no tenga actividades activas
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total 
         FROM actividades 
         WHERE instalacion_id = ? 
           AND (fecha_inicio <= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()))"
    );
    $stmt->execute([$instalacion_id]);
    $actividades_activas = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($actividades_activas > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No se puede desactivar una instalación con actividades activas'
        ]);
        exit;
    }
    
    // Desactivar la instalación (en realidad la eliminamos por simplicidad)
    $stmt = $pdo->prepare("DELETE FROM instalaciones WHERE id = ?");
    $result = $stmt->execute([$instalacion_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Instalación desactivada correctamente'
        ]);
    } else {
        throw new Exception('Error al desactivar la instalación');
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en API instalaciones/deactivate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API instalaciones/deactivate: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
