<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';

header('Content-Type: application/json');

// Verificar autenticación
$admin = verifyAuth();
$admin_id = $admin['id'];
$admin_role = $admin['role'];

// Solo los superadmins pueden acceder a los logs
if ($admin_role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo los superadministradores pueden acceder a los logs.']);
    exit;
}

try {
    // Obtener logs de actividad
    // En una implementación real, estos logs vendrían de una tabla en la base de datos
    // Por ahora, vamos a simular algunos logs
    
    $logs = [
        [
            'id' => 1,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
            'type' => 'info',
            'message' => 'Usuario administrador ha iniciado sesión',
            'user_id' => 1,
            'user_name' => 'Admin Principal'
        ],
        [
            'id' => 2,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            'type' => 'warning',
            'message' => 'Se ha detectado una actividad con horario duplicado',
            'user_id' => 2,
            'user_name' => 'Admin Secundario'
        ],
        [
            'id' => 3,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
            'type' => 'success',
            'message' => 'Nueva actividad creada correctamente',
            'user_id' => 1,
            'user_name' => 'Admin Principal'
        ],
        [
            'id' => 4,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-20 minutes')),
            'type' => 'error',
            'message' => 'Error al actualizar instalación',
            'user_id' => 3,
            'user_name' => 'Admin Terciario'
        ],
        [
            'id' => 5,
            'timestamp' => date('Y-m-d H:i:s', strtotime('-25 minutes')),
            'type' => 'info',
            'message' => 'Se ha actualizado la información de un centro',
            'user_id' => 1,
            'user_name' => 'Admin Principal'
        ]
    ];
    
    // En una implementación real, podríamos filtrar los logs por fecha, tipo, usuario, etc.
    // $since = isset($_GET['since']) ? $_GET['since'] : null;
    // $type = isset($_GET['type']) ? $_GET['type'] : null;
    // $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
