<?php
/**
 * Cerrar sesión del administrador
 */

header('Content-Type: application/json');
session_start();

try {
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
