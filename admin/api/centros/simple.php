<?php
/**
 * Endpoint simple para listar centros - Arquitectura limpia
 * Sin complejidad innecesaria, solo lo esencial
 */

header('Content-Type: application/json');

try {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticación básica
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No autenticado'
        ]);
        exit;
    }
    
    // Conectar a la base de datos
    require_once '../../../config/config.php';
    
    // Consulta simple - solo lo que necesitamos
    $query = "SELECT id, nombre, direccion FROM centros ORDER BY nombre ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $centros,
        'total' => count($centros)
    ]);
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en centros/simple.php: " . $e->getMessage());
    
    // Respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage() // Solo para desarrollo
    ]);
}
?>
