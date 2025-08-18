<?php
require_once '../../auth_middleware.php';
require_once '../../../config/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $admin = getAdminInfo();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if (strlen($new) < 8) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
        exit;
    }

    if ($new !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'La confirmación no coincide.']);
        exit;
    }

    // Obtener hash actual del admin
    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        exit;
    }

    if (!password_verify($current, $row['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
        exit;
    }

    // Actualizar contraseña
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
    $upd->execute([$newHash, $admin['id']]);

    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
} catch (Throwable $e) {
    error_log('Error en change_password.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error del sistema. Inténtalo más tarde.']);
}
