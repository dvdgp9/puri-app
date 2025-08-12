<?php
/**
 * Debug endpoint para diagnosticar problemas con centros
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo json_encode([
        'step' => 1,
        'message' => 'Iniciando debug...',
        'success' => true
    ]);
    
    // Paso 1: Verificar config
    require_once '../../../config/config.php';
    
    echo json_encode([
        'step' => 2,
        'message' => 'Config cargado correctamente',
        'success' => true
    ]);
    
    // Paso 2: Verificar auth middleware
    require_once '../../auth_middleware.php';
    
    echo json_encode([
        'step' => 3,
        'message' => 'Auth middleware cargado',
        'success' => true
    ]);
    
    // Paso 3: Verificar autenticación
    $admin_info = getAdminInfo();
    
    echo json_encode([
        'step' => 4,
        'message' => 'Admin autenticado: ' . $admin_info['username'],
        'admin_role' => $admin_info['role'],
        'success' => true
    ]);
    
    // Paso 4: Verificar conexión a BD
    global $pdo;
    if (!$pdo) {
        throw new Exception('PDO no está disponible');
    }
    
    echo json_encode([
        'step' => 5,
        'message' => 'Conexión PDO verificada',
        'success' => true
    ]);
    
    // Paso 5: Verificar tabla centros
    $stmt = $pdo->query("DESCRIBE centros");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'step' => 6,
        'message' => 'Tabla centros verificada',
        'columns' => array_column($columns, 'Field'),
        'success' => true
    ]);
    
    // Paso 6: Contar centros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM centros");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'step' => 7,
        'message' => 'Centros encontrados: ' . $total,
        'total_centros' => $total,
        'success' => true
    ]);
    
    // Paso 7: Consulta simple
    $stmt = $pdo->query("SELECT id, nombre, direccion FROM centros LIMIT 3");
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'step' => 8,
        'message' => 'Consulta simple exitosa',
        'sample_centros' => $centros,
        'success' => true,
        'final' => true
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
