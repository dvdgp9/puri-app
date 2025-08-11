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
    
    // Obtener centros asignados
    $centros = getAssignedCentros($pdo, $admin_id, $admin_role);
    
    // Filtrar centros si se especifica un centro_id
    if ($centro_id) {
        $centros = array_filter($centros, function($centro) use ($centro_id) {
            return $centro['id'] == $centro_id;
        });
    }
    
    $centros_count = count($centros);
    
    // Obtener instalaciones
    if ($admin_role === 'superadmin') {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones WHERE centro_id = ?");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones");
            $stmt->execute();
        }
        $instalaciones = $stmt->fetch(PDO::FETCH_ASSOC);
        $instalaciones_count = $instalaciones['count'];
    } else {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones WHERE centro_id = ?");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM instalaciones i JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ?");
            $stmt->execute([$admin_id]);
        }
        $instalaciones = $stmt->fetch(PDO::FETCH_ASSOC);
        $instalaciones_count = $instalaciones['count'];
    }
    
    // Obtener actividades
    if ($admin_role === 'superadmin') {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ?");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades");
            $stmt->execute();
        }
        $actividades = $stmt->fetch(PDO::FETCH_ASSOC);
        $actividades_count = $actividades['count'];
    } else {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ?");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ?");
            $stmt->execute([$admin_id]);
        }
        $actividades = $stmt->fetch(PDO::FETCH_ASSOC);
        $actividades_count = $actividades['count'];
    }
    
    // Obtener distribución de actividades por estado
    if ($admin_role === 'superadmin') {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT estado, COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? GROUP BY estado");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT estado, COUNT(*) as count FROM actividades GROUP BY estado");
            $stmt->execute();
        }
    } else {
        if ($centro_id) {
            $stmt = $pdo->prepare("SELECT estado, COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id WHERE i.centro_id = ? GROUP BY estado");
            $stmt->execute([$centro_id]);
        } else {
            $stmt = $pdo->prepare("SELECT estado, COUNT(*) as count FROM actividades a JOIN instalaciones i ON a.instalacion_id = i.id JOIN admin_asignaciones aa ON i.centro_id = aa.centro_id WHERE aa.admin_id = ? GROUP BY estado");
            $stmt->execute([$admin_id]);
        }
    }
    
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'stats' => [
                'centros' => $centros_count,
                'instalaciones' => $instalaciones_count,
                'actividades' => $actividades_count,
                'estados' => $estados
            ]
        ]
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
