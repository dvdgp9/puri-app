<?php
/**
 * Guardar (reemplazar) asignaciones de centros para un admin (solo Superadmin)
 * POST JSON: { admin_id: number, center_ids: number[] }
 * Respuesta: { success: boolean, count: number }
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

    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $admin_id = isset($json['admin_id']) ? (int)$json['admin_id'] : 0;
    $center_ids = isset($json['center_ids']) && is_array($json['center_ids']) ? $json['center_ids'] : [];

    if ($admin_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'admin_id inválido']);
        exit;
    }

    // Normalizar y filtrar IDs de centros
    $clean_ids = [];
    foreach ($center_ids as $cid) {
        $cid = (int)$cid;
        if ($cid > 0) $clean_ids[] = $cid;
    }

    $pdo->beginTransaction();

    // Eliminar asignaciones previas
    $del = $pdo->prepare('DELETE FROM admin_asignaciones WHERE admin_id = ?');
    $del->execute([$admin_id]);

    $count = 0;
    if (!empty($clean_ids)) {
        // Insertar nuevas asignaciones (bulk)
        $placeholders = [];
        $params = [];
        foreach ($clean_ids as $cid) {
            $placeholders[] = '(?, ?)';
            $params[] = $admin_id;
            $params[] = $cid;
        }
        $sql = 'INSERT INTO admin_asignaciones (admin_id, centro_id) VALUES ' . implode(',', $placeholders);
        $ins = $pdo->prepare($sql);
        $ins->execute($params);
        $count = $ins->rowCount();
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error en centers_save: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
