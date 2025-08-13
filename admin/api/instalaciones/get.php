<?php
/**
 * Obtener datos de una instalación específica
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
    
    // Obtener datos de la instalación
    $stmt = $pdo->prepare(
        "SELECT id, nombre, centro_id 
         FROM instalaciones 
         WHERE id = ?"
    );
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
    
    echo json_encode([
        'success' => true,
        'data' => $instalacion
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en API instalaciones/get: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en API instalaciones/get: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
