<?php
/**
 * Verificar sesión de administrador sin redirecciones.
 */
require_once 'auth_middleware.php';

header('Content-Type: application/json');

if (!isLoggedIn() && !restoreAdminSessionFromRememberCookie()) {
    echo json_encode(['authenticated' => false, 'redirect' => 'login.php']);
    exit;
}

if (!isset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_role'])) {
    session_unset();
    session_destroy();
    echo json_encode(['authenticated' => false, 'redirect' => 'login.php']);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role'],
        'isSuperAdmin' => $_SESSION['admin_role'] === 'superadmin'
    ]
]);
