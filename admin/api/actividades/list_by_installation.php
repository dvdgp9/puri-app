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
            echo json_encode(['success' => false, 'message' => 'No autorizado para esta instalación']);
            exit;
        }
    }
    
    // Obtener actividades de la instalación, incluyendo conteo de participantes
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.nombre, 
            a.grupo,
            a.dias_semana, 
            a.hora_inicio, 
            a.hora_fin, 
            a.fecha_inicio, 
            a.fecha_fin,
            (SELECT COUNT(*) FROM inscritos i WHERE i.actividad_id = a.id) AS participantes_count
        FROM actividades a
        WHERE a.instalacion_id = ? 
        ORDER BY a.nombre
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
