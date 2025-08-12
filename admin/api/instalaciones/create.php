<?php
require_once '../../../config/config.php';

// Verificar autenticación de admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Validar campos requeridos
$nombre = trim($input['nombre'] ?? '');
$centro_id = intval($input['centro_id'] ?? 0);

if (empty($nombre)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
    exit;
}

if ($centro_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe seleccionar un centro']);
    exit;
}

try {
    // Verificar que el centro existe y el admin tiene acceso
    $admin_id = $_SESSION['admin_id'];
    $is_superadmin = $_SESSION['is_superadmin'] ?? false;
    
    if (!$is_superadmin) {
        // Verificar que el admin tiene acceso a este centro
        $stmt = $pdo->prepare("SELECT id FROM centros WHERE id = ? AND admin_id = ?");
        $stmt->execute([$centro_id, $admin_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes acceso a este centro']);
            exit;
        }
    } else {
        // Verificar que el centro existe
        $stmt = $pdo->prepare("SELECT id FROM centros WHERE id = ?");
        $stmt->execute([$centro_id]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Centro no encontrado']);
            exit;
        }
    }
    
    // Verificar que no existe una instalación con el mismo nombre en el centro
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE nombre = ? AND centro_id = ?");
    $stmt->execute([$nombre, $centro_id]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya existe una instalación con ese nombre en este centro']);
        exit;
    }
    
    // Crear la instalación
    $stmt = $pdo->prepare("INSERT INTO instalaciones (nombre, centro_id) VALUES (?, ?)");
    $result = $stmt->execute([$nombre, $centro_id]);
    
    if ($result) {
        $instalacion_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Instalación creada exitosamente',
            'instalacion_id' => $instalacion_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la instalación']);
    }
    
} catch (PDOException $e) {
    error_log("Error creating instalacion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
