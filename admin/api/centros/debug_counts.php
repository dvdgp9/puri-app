<?php
/**
 * Debug para verificar las consultas con conteos
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Cargar configuración
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    $debug = [];
    
    // Paso 1: Verificar autenticación
    $admin_info = getAdminInfo();
    $debug[] = ['step' => 1, 'message' => 'Auth OK', 'admin' => $admin_info['username']];
    
    // Paso 2: Verificar tablas existen
    $tables = ['centros', 'instalaciones', 'actividades'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $debug[] = ['step' => 2, 'table' => $table, 'exists' => $exists];
    }
    
    // Paso 3: Consulta simple de centros
    $stmt = $pdo->query("SELECT id, nombre, direccion FROM centros LIMIT 3");
    $centros_simple = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug[] = ['step' => 3, 'message' => 'Centros simples', 'count' => count($centros_simple), 'data' => $centros_simple];
    
    // Paso 4: Verificar instalaciones
    $stmt = $pdo->query("SELECT centro_id, COUNT(*) as count FROM instalaciones GROUP BY centro_id LIMIT 3");
    $instalaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug[] = ['step' => 4, 'message' => 'Instalaciones por centro', 'data' => $instalaciones];
    
    // Paso 5: Verificar actividades
    $stmt = $pdo->query("SELECT centro_id, COUNT(*) as count FROM actividades GROUP BY centro_id LIMIT 3");
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug[] = ['step' => 5, 'message' => 'Actividades por centro', 'data' => $actividades];
    
    // Paso 6: Intentar consulta con JOIN simple
    $query = "
        SELECT 
            c.id, 
            c.nombre, 
            c.direccion,
            COUNT(DISTINCT i.id) as total_instalaciones
        FROM centros c
        LEFT JOIN instalaciones i ON c.id = i.centro_id
        GROUP BY c.id, c.nombre, c.direccion
        LIMIT 2
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug[] = ['step' => 6, 'message' => 'JOIN simple', 'query' => $query, 'result' => $resultado];
    
    echo json_encode(['success' => true, 'debug' => $debug]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile(),
        'debug' => $debug ?? []
    ]);
}
?>
