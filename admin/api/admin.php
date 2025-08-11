<?php
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once 'auth.php';

// Verificar autenticación
requireAuth();

$admin = getCurrentAdmin();

// Para obtener datos del propio admin, no se requiere ser superadmin
// Para obtener datos de otro admin, se requiere ser superadmin
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : $admin['id'];

if ($admin_id != $admin['id'] && $admin['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, email, role FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_data) {
        echo json_encode(['success' => true, 'data' => $admin_data]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Administrador no encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>
