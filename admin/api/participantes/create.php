<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Cargar configuración y autenticación
    require_once '../../../config/config.php';
    require_once '../../auth_middleware.php';
    
    // Verificar autenticación de admin
    $admin_info = getAdminInfo();

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
    $apellidos = trim($input['apellidos'] ?? '');
    $actividad_id = intval($input['actividad_id'] ?? 0);

    // Validaciones
    if (empty($nombre)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }

    if (empty($apellidos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Los apellidos son obligatorios']);
        exit;
    }

    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar una actividad']);
        exit;
    }

    // Verificar que la actividad existe
    $stmt = $pdo->prepare("SELECT id FROM actividades WHERE id = ?");
    $stmt->execute([$actividad_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }
    
    // Verificar que no existe ya este participante en la actividad
    $stmt = $pdo->prepare("SELECT id FROM inscritos WHERE actividad_id = ? AND nombre = ? AND apellidos = ?");
    $stmt->execute([$actividad_id, $nombre, $apellidos]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Este participante ya está inscrito en la actividad']);
        exit;
    }
    
    // Crear el participante
    $stmt = $pdo->prepare("INSERT INTO inscritos (actividad_id, nombre, apellidos) VALUES (?, ?, ?)");
    
    $result = $stmt->execute([$actividad_id, $nombre, $apellidos]);
    
    if ($result) {
        $participante_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Participante inscrito exitosamente',
            'participante_id' => $participante_id
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al inscribir el participante']);
        exit;
    }
    
} catch (Exception $e) {
    error_log("Error creating participante: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
