<?php
/**
 * API para listar registros de aforo de una actividad
 * GET: actividad_id, fecha (opcional, default: hoy)
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $actividad_id = intval($_GET['actividad_id'] ?? 0);
    $fecha = trim($_GET['fecha'] ?? date('Y-m-d'));
    
    if ($actividad_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de actividad inválido']);
        exit;
    }
    
    // Verificar que la actividad existe y pertenece al centro
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
    
    // Obtener registros del día
    $stmt = $pdo->prepare("
        SELECT id, fecha, hora, num_personas, created_at
        FROM registros_aforo
        WHERE actividad_id = ? AND fecha = ?
        ORDER BY hora DESC
    ");
    $stmt->execute([$actividad_id, $fecha]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular total del día
    $total_dia = array_sum(array_column($registros, 'num_personas'));
    
    echo json_encode([
        'success' => true,
        'actividad' => [
            'id' => $actividad['id'],
            'nombre' => $actividad['nombre']
        ],
        'fecha' => $fecha,
        'registros' => $registros,
        'total_dia' => $total_dia,
        'num_registros' => count($registros)
    ]);
    
} catch (Exception $e) {
    error_log("Error en aforo/listar.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
