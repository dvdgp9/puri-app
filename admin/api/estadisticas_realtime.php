<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once 'auth.php';

// Verificar autenticación
requireAuth();

$admin = getCurrentAdmin();
$admin_id = $admin['id'];
$admin_role = $admin['role'];

try {
    // Obtener parámetros de filtro
    $centro_id = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : null;
    
    // Obtener estadísticas en tiempo real (última hora)
    $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // Obtener actividades nuevas (creadas en la última hora)
    if ($admin_role === 'superadmin') {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? AND a.created_at >= ?");
            $stmt->execute([$centro_id, $one_hour_ago]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades WHERE created_at >= ?");
            $stmt->execute([$one_hour_ago]);
        }
    } else {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? AND a.created_at >= ?");
            $stmt->execute([$centro_id, $one_hour_ago]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND a.created_at >= ?");
            $stmt->execute([$admin_id, $one_hour_ago]);
        }
    }
    
    $new_activities = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_activities_count = $new_activities['count'];
    
    // Obtener actividades completadas (marcadas como finalizadas en la última hora)
    if ($admin_role === 'superadmin') {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? AND a.estado = 'finalizada' AND a.updated_at >= ?");
            $stmt->execute([$centro_id, $one_hour_ago]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades WHERE estado = 'finalizada' AND updated_at >= ?");
            $stmt->execute([$one_hour_ago]);
        }
    } else {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? AND a.estado = 'finalizada' AND a.updated_at >= ?");
            $stmt->execute([$centro_id, $one_hour_ago]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? AND a.estado = 'finalizada' AND a.updated_at >= ?");
            $stmt->execute([$admin_id, $one_hour_ago]);
        }
    }
    
    $completed_activities = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_activities_count = $completed_activities['count'];
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'stats' => [
                'realtime' => [
                    'new_activities' => $new_activities_count,
                    'completed_activities' => $completed_activities_count
                ]
            ]
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
