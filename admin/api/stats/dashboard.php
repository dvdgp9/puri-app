<?php
/**
 * Estadísticas para el dashboard principal
 */

session_start();
header('Content-Type: application/json');
require_once '../../../config/config.php';

// Verificar autenticación sin redirecciones
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

try {
    $admin_info = [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
    
    // Base para filtrar por centros asignados si no es superadmin
    $centro_filter = '';
    $params = [];
    
    if ($admin_info['role'] !== 'superadmin') {
        $centro_filter = "AND c.id IN (SELECT centro_id FROM admin_asignaciones WHERE admin_id = ?)";
        $params[] = $admin_info['id'];
    }
    
    // Estadísticas básicas
    $stats = [];
    
    // Total de centros
    $query = "SELECT COUNT(*) as total FROM centros c WHERE 1=1 $centro_filter";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stats['total_centros'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de instalaciones
    $query = "
        SELECT COUNT(*) as total 
        FROM instalaciones i 
        INNER JOIN centros c ON i.centro_id = c.id 
        WHERE 1=1 $centro_filter
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stats['total_instalaciones'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de actividades
    $query = "
        SELECT COUNT(*) as total 
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        INNER JOIN centros c ON i.centro_id = c.id 
        WHERE 1=1 $centro_filter
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stats['total_actividades'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Actividades por estado
    $query = "
        SELECT 
            CASE 
                WHEN a.fecha_inicio > CURDATE() THEN 'Programada'
                WHEN a.fecha_fin < CURDATE() THEN 'Finalizada'
                ELSE 'Activa'
            END as estado,
            COUNT(*) as total
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        INNER JOIN centros c ON i.centro_id = c.id 
        WHERE 1=1 $centro_filter
        GROUP BY estado
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats['actividades_por_estado'] = [
        'Programada' => 0,
        'Activa' => 0,
        'Finalizada' => 0
    ];
    
    foreach ($estados as $estado) {
        $stats['actividades_por_estado'][$estado['estado']] = (int)$estado['total'];
    }
    
    // Centros con más actividades (top 5)
    $query = "
        SELECT 
            c.nombre,
            COUNT(a.id) as total_actividades
        FROM centros c
        LEFT JOIN instalaciones i ON c.id = i.centro_id
        LEFT JOIN actividades a ON i.id = a.instalacion_id
        WHERE 1=1 $centro_filter
        GROUP BY c.id, c.nombre
        ORDER BY total_actividades DESC
        LIMIT 5
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stats['top_centros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Actividades recientes (últimas 10)
    $query = "
        SELECT 
            a.nombre,
            c.nombre as centro_nombre,
            i.nombre as instalacion_nombre,
            a.fecha_inicio,
            a.fecha_fin,
            CASE 
                WHEN a.fecha_inicio > CURDATE() THEN 'Programada'
                WHEN a.fecha_fin < CURDATE() THEN 'Finalizada'
                ELSE 'Activa'
            END as estado
        FROM actividades a
        INNER JOIN instalaciones i ON a.instalacion_id = i.id
        INNER JOIN centros c ON i.centro_id = c.id
        WHERE 1=1 $centro_filter
        ORDER BY a.created_at DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $stats['actividades_recientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error en API stats/dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
