<?php
/**
 * Cerrar sesión de administrador
 */

session_start();

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir el token de persistencia si existe
if (isset($_COOKIE['admin_remember_token'])) {
    require_once '../config/config.php';
    $token = $_COOKIE['admin_remember_token'];
    $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE token = ?");
    $stmt->execute([$token]);
    setcookie('admin_remember_token', '', time() - 3600, '/');
}

session_destroy();

// Redirigir al login
header("Location: login.php");
exit;
?>
