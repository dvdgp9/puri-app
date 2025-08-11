<?php
/**
 * Verificar autenticación del administrador
 */

header('Content-Type: application/json');
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';

try {
    // El middleware ya verificó la autenticación
    $admin_info = getAdminInfo();
    
    // Obtener información adicional del administrador
    $stmt = $pdo->prepare("SELECT username, role, created_at FROM admins WHERE id = ?");
    $stmt->execute([$admin_info['id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        throw new Exception('Administrador no encontrado');
    }
    
    // Obtener centros asignados si no es superadmin
    $assigned_centers = [];
    if ($admin['role'] !== 'superadmin') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.nombre 
            FROM centros c 
            INNER JOIN admin_asignaciones aa ON c.id = aa.centro_id 
            WHERE aa.admin_id = ?
        ");
        $stmt->execute([$admin_info['id']]);
        $assigned_centers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $admin_info['id'],
            'username' => $admin['username'],
            'role' => $admin['role'],
            'isSuperAdmin' => $admin['role'] === 'superadmin',
            'assignedCenters' => $assigned_centers,
            'createdAt' => $admin['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'No autorizado'
    ]);
}
?>
