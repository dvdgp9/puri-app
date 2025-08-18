<?php
/**
 * Listar todos los centros con flag de asignación para un admin concreto (solo Superadmin)
 * GET params: admin_id
 * Respuesta: { success, data: [ { id, nombre, direccion, activo, asignado } ] }
 */

header('Content-Type: application/json');
require_once '../../../auth_middleware.php';
require_once '../../../../config/config.php';

try {
    if (!isSuperAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    }

    $admin_id = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
    if ($admin_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetro admin_id inválido']);
        exit;
    }

    $sql = "
        SELECT 
            c.id,
            c.nombre,
            c.direccion,
            c.activo,
            CASE WHEN aa.admin_id IS NULL THEN 0 ELSE 1 END AS asignado
        FROM centros c
        LEFT JOIN admin_asignaciones aa
            ON aa.centro_id = c.id AND aa.admin_id = ?
        ORDER BY c.nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$admin_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar tipos
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['activo'] = isset($r['activo']) ? (int)$r['activo'] : 0;
        $r['asignado'] = (int)$r['asignado'] === 1;
    }

    echo json_encode(['success' => true, 'data' => $rows]);

} catch (Exception $e) {
    error_log('Error en centers_list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
