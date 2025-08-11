<?php
/**
 * Middleware de autenticación para administradores
 * Incluir este archivo en todas las páginas que requieran autenticación de admin
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireAdminAuth() {
    // Verificar si el administrador está logueado
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirigir al login si no está autenticado
        header("Location: login.php");
        exit;
    }
    
    // Verificar que la sesión tenga los datos necesarios
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username']) || !isset($_SESSION['admin_role'])) {
        // Limpiar sesión corrupta
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }
}

function requireSuperAdmin() {
    requireAdminAuth();
    
    if ($_SESSION['admin_role'] !== 'superadmin') {
        // Redirigir al dashboard si no es superadmin
        header("Location: dashboard.php?error=access_denied");
        exit;
    }
}

function getAdminInfo() {
    requireAdminAuth();
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'role' => $_SESSION['admin_role']
    ];
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['admin_role'] === 'superadmin';
}

function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Auto-ejecutar verificación de autenticación si se incluye este archivo
// (excepto en login.php y process_login.php)
$current_file = basename($_SERVER['PHP_SELF']);
if (!in_array($current_file, ['login.php', 'process_login.php', 'index.html'])) {
    requireAdminAuth();
}
?>
