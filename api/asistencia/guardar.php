<?php
/**
 * API para guardar asistencia individual vía AJAX
 * POST: { actividad_id, usuario_id, fecha, asistio }
 */
require_once '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Leer JSON del body
$input = json_decode(file_get_contents('php://input'), true);

$actividad_id = filter_var($input['actividad_id'] ?? null, FILTER_VALIDATE_INT);
$usuario_id = filter_var($input['usuario_id'] ?? null, FILTER_VALIDATE_INT);
$fecha = $input['fecha'] ?? date('Y-m-d');
$asistio = isset($input['asistio']) ? (int)$input['asistio'] : 0;

// Validaciones
if (!$actividad_id || !$usuario_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
    exit;
}

// Asegurar que asistio sea 0 o 1
$asistio = $asistio ? 1 : 0;

try {
    // Verificar si ya existe un registro para este usuario/actividad/fecha
    $stmt_check = $pdo->prepare("SELECT id FROM asistencias WHERE actividad_id = ? AND usuario_id = ? AND fecha = ?");
    $stmt_check->execute([$actividad_id, $usuario_id, $fecha]);
    $existing = $stmt_check->fetchColumn();
    
    if ($existing) {
        // Actualizar registro existente
        $stmt = $pdo->prepare("UPDATE asistencias SET asistio = ? WHERE actividad_id = ? AND usuario_id = ? AND fecha = ?");
        $stmt->execute([$asistio, $actividad_id, $usuario_id, $fecha]);
    } else {
        // Insertar nuevo registro
        $stmt = $pdo->prepare("INSERT INTO asistencias (actividad_id, usuario_id, fecha, asistio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$actividad_id, $usuario_id, $fecha, $asistio]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Asistencia guardada',
        'data' => [
            'actividad_id' => $actividad_id,
            'usuario_id' => $usuario_id,
            'fecha' => $fecha,
            'asistio' => $asistio
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error guardando asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar asistencia']);
}
