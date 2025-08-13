<?php
/**
 * Actualizar una instalación
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
    $instalacion_id = isset($input['id']) ? intval($input['id']) : 0;
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    
    if ($instalacion_id <= 0 || empty($nombre)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Datos incompletos'
        ]);
        exit;
    }
    
    // Verificar que la instalación existe
    $stmt = $pdo->prepare("SELECT id, centro_id FROM instalaciones WHERE id = ?");
    $stmt->execute([$instalacion_id]);
    $instalacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instalacion) {
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
    
    // Verificar que no existe otra instalación con el mismo nombre en el centro
    $stmt = $pdo->prepare(
        "SELECT id FROM instalaciones 
         WHERE nombre = ? AND centro_id = ? AND id != ?"
    );
    $stmt->execute([$nombre, $instalacion['centro_id'], $instalacion_id]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Ya existe una instalación con ese nombre en este centro'
        ]);
        exit;
    }
    
    // Actualizar la instalación
    $stmt = $pdo->prepare(
        "UPDATE instalaciones 
         SET nombre = ? 
         WHERE id = ?"
    );
    $result = $stmt->execute([$nombre, $instalacion_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Instalación actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la instalación');
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en API instalaciones/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API instalaciones/update: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
