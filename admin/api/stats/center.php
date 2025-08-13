<?php
/**
 * Estadísticas específicas para un centro
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'pasarusr');
define('DB_PASS', 'pasarcontr');
define('DB_NAME', 'pasarlistabdd');

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

// Obtener ID del centro
$centro_id = intval($_GET['id'] ?? 0);

if ($centro_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de centro inválido'
    ]);
    exit;
}

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
    
    // Verificar autorización para este centro
    if ($admin_info['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("SELECT 1 FROM admin_asignaciones WHERE admin_id = ? AND centro_id = ?");
        $stmt->execute([$admin_info['id'], $centro_id]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'No autorizado para este centro'
            ]);
            exit;
        }
    }
    
    // Verificar que el centro existe
    $stmt = $pdo->prepare("SELECT nombre FROM centros WHERE id = ?");
    $stmt->execute([$centro_id]);
    $centro = $stmt->fetch();
    
    if (!$centro) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Centro no encontrado'
        ]);
        exit;
    }
    
    $stats = [];
    
    // Total de instalaciones del centro
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instalaciones WHERE centro_id = ?");
    $stmt->execute([$centro_id]);
    $stats['total_instalaciones'] = (int)$stmt->fetch()['total'];
    
    // Actividades activas (entre fecha_inicio y fecha_fin o sin fecha_fin)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ? 
        AND (a.fecha_inicio <= CURDATE() AND (a.fecha_fin IS NULL OR a.fecha_fin >= CURDATE()))
    ");
    $stmt->execute([$centro_id]);
    $stats['actividades_activas'] = (int)$stmt->fetch()['total'];
    
    // Actividades programadas (fecha_inicio > hoy)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM actividades a 
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ? 
        AND a.fecha_inicio > CURDATE()
    ");
    $stmt->execute([$centro_id]);
    $stats['actividades_programadas'] = (int)$stmt->fetch()['total'];
    
    // Total de participantes (inscritos en actividades del centro)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM inscritos ins
        INNER JOIN actividades a ON ins.actividad_id = a.id
        INNER JOIN instalaciones i ON a.instalacion_id = i.id 
        WHERE i.centro_id = ?
    ");
    $stmt->execute([$centro_id]);
    $stats['total_participantes'] = (int)$stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Error de BD en API stats/center: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos'
    ]);
} catch (Exception $e) {
    error_log("Error en API stats/center: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>
