<?php
/**
 * API para registrar aforo en una actividad
 * POST: actividad_id, fecha, hora, num_personas
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once '../../config/config.php';
    
    // Verificar sesión de centro
    if (!isset($_SESSION['centro_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $centro_id = $_SESSION['centro_id'];
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }
    
    $actividad_id = intval($input['actividad_id'] ?? 0);
    $fecha = trim($input['fecha'] ?? '');
    $hora = trim($input['hora'] ?? '');
    $num_personas = intval($input['num_personas'] ?? 0);
    
    // Validaciones
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }
    
    if (empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fecha inválida (formato: YYYY-MM-DD)']);
        exit;
    }
    
    if (empty($hora) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hora inválida (formato: HH:MM)']);
        exit;
    }
    
    if ($num_personas < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El número de personas no puede ser negativo']);
        exit;
    }
    
    // Verificar que la actividad existe, es de tipo aforo y pertenece al centro
    $stmt = $pdo->prepare("
        SELECT a.id, a.nombre, a.tipo_control, i.centro_id
        FROM actividades a
        JOIN instalaciones i ON a.instalacion_id = i.id
        WHERE a.id = ?
    ");
    $stmt->execute([$actividad_id]);
    $actividad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actividad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Actividad no encontrada']);
        exit;
    }
    
    if ($actividad['centro_id'] != $centro_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado para esta actividad']);
        exit;
    }
    
    if ($actividad['tipo_control'] !== 'aforo') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Esta actividad no es de tipo aforo']);
        exit;
    }
    
    // Insertar registro de aforo
    $stmt = $pdo->prepare("
        INSERT INTO registros_aforo (actividad_id, fecha, hora, num_personas, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$actividad_id, $fecha, $hora, $num_personas, $centro_id]);
    
    if ($result) {
        $registro_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Aforo registrado correctamente',
            'registro_id' => $registro_id,
            'data' => [
                'id' => $registro_id,
                'actividad_id' => $actividad_id,
                'fecha' => $fecha,
                'hora' => $hora,
                'num_personas' => $num_personas
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al registrar aforo']);
    }
    
} catch (Exception $e) {
    error_log("Error en aforo/registrar.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
