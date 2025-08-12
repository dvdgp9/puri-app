<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración
    require_once '../../../config/config.php';
    
    // Verificar autenticación de admin (simplificado)
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $admin_id = $_SESSION['admin_id'];
    $is_superadmin = ($_SESSION['admin_role'] === 'superadmin');
    
    if ($is_superadmin) {
        // Superadmin ve todos los centros
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE activo = 1 ORDER BY nombre");
        $stmt->execute();
    } else {
        // Admin normal solo ve sus centros
        $stmt = $pdo->prepare("SELECT id, nombre FROM centros WHERE admin_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$admin_id]);
    }
    
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'centros' => $centros
    ]);
    
} catch (Exception $e) {
    error_log("Error in list_for_selector.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>
