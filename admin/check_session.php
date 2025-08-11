<?php
/**
 * Verificar sesión de administrador sin redirecciones
 * Para uso de la SPA
 */

session_start();

header('Content-Type: application/json');

// Verificar si el administrador está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([
        'authenticated' => false,
        'redirect' => 'login.php'
    ]);
    exit;
}

// Verificar que la sesión tenga los datos necesarios
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['admin_role'])) {
    // Limpiar sesión corrupta
    session_unset();
    session_destroy();
    echo json_encode([
        'authenticated' => false,
        'redirect' => 'login.php'
    ]);
    exit;
}

// Usuario autenticado correctamente
echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role'],
        'isSuperAdmin' => $_SESSION['admin_role'] === 'superadmin'
    ]
]);
?>
