<?php
/**
 * Crear un nuevo administrador (solo Superadmin)
 * Entrada JSON: { username, password, role }
 * Respuesta: { success, data: { id, username, role, created_at } } | { success:false, error }
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
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido']);
        exit;
    }

    $username = trim($payload['username'] ?? '');
    $password = (string)($payload['password'] ?? '');
    $role = trim($payload['role'] ?? '');

    // Validaciones
    if ($username === '' || $password === '' || $role === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
        exit;
    }

    $valid_roles = ['admin', 'superadmin'];
    if (!in_array($role, $valid_roles, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Rol inválido']);
        exit;
    }

    // Username único
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya existe']);
        exit;
    }

    // Crear
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, $password_hash, $role]);
    $newId = (int)$pdo->lastInsertId();

    // Recuperar registro creado
    $stmt = $pdo->prepare('SELECT id, username, role, created_at FROM admins WHERE id = ?');
    $stmt->execute([$newId]);
    $created = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $created]);

} catch (Exception $e) {
    error_log('Error en API superadmin/admins/create: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
