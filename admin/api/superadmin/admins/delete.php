<?php
/**
 * Eliminar un administrador (solo Superadmin)
 * Entrada JSON: { id }
 * Restricciones:
 * - No puedes eliminarte a ti mismo.
 * - No puedes eliminar al último superadmin.
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

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $id = isset($payload['id']) ? (int)$payload['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }

    // No auto-eliminarse
    $current = getAdminInfo();
    if ($current['id'] === $id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No puedes eliminar tu propio usuario']);
        exit;
    }

    // Obtener admin a eliminar
    $stmt = $pdo->prepare('SELECT id, username, role FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Administrador no encontrado']);
        exit;
    }

    // Evitar borrar último superadmin
    if ($admin['role'] === 'superadmin') {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM admins WHERE role = 'superadmin'");
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        if ($count <= 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No puedes eliminar al último superadmin']);
            exit;
        }
    }

    // Ejecutar borrado
    $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'data' => ['deletedId' => $id]]);

} catch (Exception $e) {
    error_log('Error en API superadmin/admins/delete: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
