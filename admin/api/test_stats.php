<?php
/**
 * Test endpoint para estadísticas - versión simplificada para debug
 */

session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

// Configuración de la base de datos (copia para evitar problemas)
define('DB_HOST', 'localhost');
define('DB_USER', 'pasarusr');
define('DB_PASS', 'pasarcontr');
define('DB_NAME', 'pasarlistabdd');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $admin_info = [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
    
    // Estadísticas básicas simplificadas
    $stats = [];
    
    // Total de centros
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM centros");
        $stats['total_centros'] = (int)$stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats['total_centros'] = 0;
        $stats['error_centros'] = $e->getMessage();
    }
    
    // Total de instalaciones
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instalaciones");
        $stats['total_instalaciones'] = (int)$stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats['total_instalaciones'] = 0;
        $stats['error_instalaciones'] = $e->getMessage();
    }
    
    // Total de actividades
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM actividades");
        $stats['total_actividades'] = (int)$stmt->fetch()['total'];
    } catch (Exception $e) {
        $stats['total_actividades'] = 0;
        $stats['error_actividades'] = $e->getMessage();
    }
    
    // Actividades por estado (simplificado)
    $stats['actividades_por_estado'] = [
        'Programada' => 0,
        'Activa' => 0,
        'Finalizada' => 0
    ];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN fecha_inicio > CURDATE() THEN 'Programada'
                    WHEN fecha_fin < CURDATE() THEN 'Finalizada'
                    ELSE 'Activa'
                END as estado,
                COUNT(*) as total
            FROM actividades 
            GROUP BY estado
        ");
        
        while ($row = $stmt->fetch()) {
            $stats['actividades_por_estado'][$row['estado']] = (int)$row['total'];
        }
    } catch (Exception $e) {
        $stats['error_estados'] = $e->getMessage();
    }
    
    // Top centros (simplificado)
    $stats['top_centros'] = [];
    try {
        $stmt = $pdo->query("
            SELECT 
                c.nombre,
                COUNT(a.id) as total_actividades
            FROM centros c
            LEFT JOIN instalaciones i ON c.id = i.centro_id
            LEFT JOIN actividades a ON i.id = a.instalacion_id
            GROUP BY c.id, c.nombre
            ORDER BY total_actividades DESC
            LIMIT 5
        ");
        $stats['top_centros'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['error_top_centros'] = $e->getMessage();
    }
    
    // Actividades recientes (simplificado)
    $stats['actividades_recientes'] = [];
    try {
        $stmt = $pdo->query("
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
            ORDER BY a.id DESC
            LIMIT 10
        ");
        $stats['actividades_recientes'] = $stmt->fetchAll();
    } catch (Exception $e) {
        $stats['error_actividades_recientes'] = $e->getMessage();
    }
    
    // Información de debug
    $stats['debug'] = [
        'admin_role' => $admin_info['role'],
        'admin_username' => $admin_info['username'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
