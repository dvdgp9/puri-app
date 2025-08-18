<?php
/**
 * Actualizar administrador (solo Superadmin)
 * Entrada JSON: { id, role?, new_password? }
 * - Permite cambiar rol a 'admin'|'superadmin'.
 * - Permite resetear contraseña si se pasa new_password (min 8).
 * Restricciones:
 * - No degradar al ÚNICO superadmin.
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
    $newRole = isset($payload['role']) ? trim($payload['role']) : null;
    $newPassword = isset($payload['new_password']) ? (string)$payload['new_password'] : null;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }

    if ($newRole !== null) {
        $valid_roles = ['admin', 'superadmin'];
        if (!in_array($newRole, $valid_roles, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Rol inválido']);
            exit;
        }
    }

    if ($newPassword !== null && strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
        exit;
    }

    // Obtener admin actual
    $stmt = $pdo->prepare('SELECT id, username, role FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Administrador no encontrado']);
        exit;
    }

    // Si se solicita degradar un superadmin a admin, verificar que no sea el único
    if ($newRole !== null && $admin['role'] === 'superadmin' && $newRole === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM admins WHERE role = 'superadmin'");
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        if ($count <= 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No puedes degradar al último superadmin']);
            exit;
        }
    }

    // Construir update dinámico
    $sets = [];
    $params = [];
    if ($newRole !== null) {
        $sets[] = 'role = ?';
        $params[] = $newRole;
    }
    if ($newPassword !== null) {
        $sets[] = 'password_hash = ?';
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (empty($sets)) {
        echo json_encode(['success' => true, 'data' => $admin]);
        exit; // Nada que actualizar
    }

    $params[] = $id;
    $sql = 'UPDATE admins SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Devolver el registro actualizado
    $stmt = $pdo->prepare('SELECT id, username, role, created_at FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $updated]);

} catch (Exception $e) {
    error_log('Error en API superadmin/admins/update: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
