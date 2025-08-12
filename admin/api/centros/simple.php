<?php
/**
 * Endpoint ultra-simple para diagnosticar el problema
 */

// Configurar headers primero
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en HTML

try {
    // Paso 1: Solo devolver JSON básico
    echo json_encode([
        'success' => true,
        'message' => 'Endpoint simple funciona',
        'data' => []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
