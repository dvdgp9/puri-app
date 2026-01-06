<?php
/**
 * Cerrar sesión del administrador
 */

header('Content-Type: application/json');
session_start();

try {
    // Destruir el token de persistencia si existe
    if (isset($_COOKIE['admin_remember_token'])) {
        require_once '../../../config/config.php';
        $token = $_COOKIE['admin_remember_token'];
        $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE token = ?");
        $stmt->execute([$token]);
        setcookie('admin_remember_token', '', time() - 3600, '/');
    }

    // Limpiar todas las variables de sesión
    session_unset();
    
    // Destruir la sesión
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al cerrar sesión'
    ]);
}
?>
