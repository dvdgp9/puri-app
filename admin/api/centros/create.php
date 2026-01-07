<?php
/**
 * API para crear centros - Admin Dashboard
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();
    
    // Solo permitir POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
    
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback para form-data
        $input = $_POST;
    }
    
    // Validar campos obligatorios
    $nombre = trim($input['nombre'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $password = trim($input['password'] ?? '');
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'error' => 'El nombre del centro es obligatorio']);
        exit;
    }
    
    if (empty($password)) {
        echo json_encode(['success' => false, 'error' => 'La contraseña del centro es obligatoria']);
        exit;
    }
    
    // Verificar que no existe un centro con el mismo nombre
    $stmt = $pdo->prepare("SELECT id FROM centros WHERE nombre = ?");
    $stmt->execute([$nombre]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe un centro con ese nombre']);
        exit;
    }
    
    // Crear el centro con contraseña hasheada
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO centros (nombre, direccion, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$nombre, $direccion, $hashed_password]);
    
    if ($result) {
        $centro_id = $pdo->lastInsertId();
        
        // Si no es superadmin, asignar el centro al admin actual
        if ($admin_info['role'] !== 'superadmin') {
            $stmt = $pdo->prepare("INSERT INTO admin_asignaciones (admin_id, centro_id) VALUES (?, ?)");
            $stmt->execute([$admin_info['id'], $centro_id]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Centro creado exitosamente',
            'data' => [
                'id' => $centro_id,
                'nombre' => $nombre,
                'direccion' => $direccion,
                'total_instalaciones' => 0,
                'total_actividades' => 0
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al crear el centro']);
    }
    
} catch (Exception $e) {
    error_log("Error en centros/create: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>
