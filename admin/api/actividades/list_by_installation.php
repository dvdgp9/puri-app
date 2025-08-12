<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();
    
    // Obtener instalacion_id del parámetro GET
    $instalacion_id = intval($_GET['instalacion_id'] ?? 0);
    
    if ($instalacion_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de instalación inválido']);
        exit;
    }
    
    // Verificar que la instalación existe
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE id = ?");
    $stmt->execute([$instalacion_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Instalación no encontrada']);
        exit;
    }
    
    // Obtener actividades de la instalación
    $stmt = $pdo->prepare("
        SELECT id, nombre, dias_semana, hora_inicio, hora_fin 
        FROM actividades 
        WHERE instalacion_id = ? 
        ORDER BY nombre
    ");
    $stmt->execute([$instalacion_id]);
    
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'actividades' => $actividades
    ]);
    
} catch (Exception $e) {
    error_log("Error in actividades/list_by_installation.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
