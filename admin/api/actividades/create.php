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
    $grupo = trim($input['grupo'] ?? '') ?: null;
    $instalacion_id = intval($input['instalacion_id'] ?? 0);
    $dias_semana = $input['dias_semana'] ?? [];
    $hora_inicio = trim($input['hora_inicio'] ?? '');
    $hora_fin = trim($input['hora_fin'] ?? '');
    $fecha_inicio = trim($input['fecha_inicio'] ?? '');
    $fecha_fin = trim($input['fecha_fin'] ?? '') ?: null;
    $tipo_control = isset($input['tipo_control']) && $input['tipo_control'] === 'aforo' ? 'aforo' : 'asistencia';

    // Validaciones
    if (empty($nombre)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
        exit;
    }

    if ($instalacion_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar una instalación']);
        exit;
    }

    if (empty($dias_semana) || !is_array($dias_semana)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos un día de la semana']);
        exit;
    }

    if (empty($hora_inicio) || empty($hora_fin)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Las horas de inicio y fin son obligatorias']);
        exit;
    }

    if (empty($fecha_inicio)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La fecha de inicio es obligatoria']);
        exit;
    }

    // Verificar que la instalación existe
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE id = ?");
    $stmt->execute([$instalacion_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Instalación no encontrada']);
        exit;
    }
    
    // Preparar datos para inserción
    $dias_semana_string = implode(',', $dias_semana);
    $horario = implode(' y ', $dias_semana) . ' ' . $hora_inicio . '-' . $hora_fin; // Campo legacy
    
    // Crear la actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividades (nombre, grupo, horario, dias_semana, hora_inicio, hora_fin, instalacion_id, fecha_inicio, fecha_fin, tipo_control) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $nombre,
        $grupo,
        $horario, 
        $dias_semana_string, 
        $hora_inicio, 
        $hora_fin, 
        $instalacion_id, 
        $fecha_inicio, 
        $fecha_fin,
        $tipo_control
    ]);
    
    if ($result) {
        $actividad_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Actividad creada exitosamente',
            'actividad_id' => $actividad_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la actividad']);
    }
    
} catch (Exception $e) {
    error_log("Error creating actividad: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
