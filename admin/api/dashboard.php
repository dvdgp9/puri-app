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
    // Obtener centros asignados
    $centros = getAssignedCentros($pdo, $admin_id, $admin_role);
    $centros_count = count($centros);
    
    // Obtener instalaciones
    if ($admin_role === 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones");
        $stmt->execute();
        $instalaciones = $stmt->fetch(PDO::FETCH_ASSOC);
        $instalaciones_count = $instalaciones['count'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones i JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ?");
        $stmt->execute([$admin_id]);
        $instalaciones = $stmt->fetch(PDO::FETCH_ASSOC);
        $instalaciones_count = $instalaciones['count'];
    }
    
    // Obtener actividades
    if ($admin_role === 'superadmin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades");
        $stmt->execute();
        $actividades = $stmt->fetch(PDO::FETCH_ASSOC);
        $actividades_count = $actividades['count'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ?");
        $stmt->execute([$admin_id]);
        $actividades = $stmt->fetch(PDO::FETCH_ASSOC);
        $actividades_count = $actividades['count'];
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'admin' => $admin,
            'stats' => [
                'centros' => $centros_count,
                'instalaciones' => $instalaciones_count,
                'actividades' => $actividades_count
            ],
            'centros' => $centros
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
