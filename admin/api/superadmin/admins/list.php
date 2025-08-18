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
        $where = 'WHERE username LIKE ?';
        $params[] = "%$search%";
    }

    $sql = "SELECT id, username, role, created_at FROM admins $where ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
