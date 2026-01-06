<?php
/**
 * Listar todos los administradores (solo Superadmin)
 * Respuesta: { success, data: [ { id, username, role, created_at } ] }
 */

header('Content-Type: application/json');
require_once '../../../auth_middleware.php';
require_once '../../../../config/config.php';

try {
    // Verificación explícita de superadmin para evitar redirecciones HTML del middleware
    if (!isSuperAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Acceso denegado'
        ]);
        exit;
    }

    // Parámetros simples opcionales
    $search = $_GET['search'] ?? '';

    $where = '';
    $params = [];
    if ($search !== '') {
        $where = 'WHERE (a.username LIKE ? OR a.nombre LIKE ? OR a.apellidos LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql = "
        SELECT 
            a.id, 
            a.username, 
            a.nombre,
            a.apellidos,
            a.role, 
            a.created_at,
            (SELECT COUNT(*) FROM admin_asignaciones WHERE admin_id = a.id) AS centers_count
        FROM admins a
        $where 
        ORDER BY created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar tipos
    foreach ($admins as &$admin) {
        $admin['id'] = (int)$admin['id'];
        $admin['centers_count'] = (int)$admin['centers_count'];
    }

    echo json_encode([
        'success' => true,
        'data' => $admins
    ]);

} catch (Exception $e) {
    error_log('Error en API superadmin/admins/list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
